<?php

declare(strict_types=1);

namespace Polis\Services\Todo;

use Illuminate\Support\Facades\DB;
use Polis\Contracts\Services\Relations\NodeTreeServiceContract;
use Polis\Models\User\TodoBalance;
use Polis\Models\User\TodoRotatingGroup;
use Polis\Models\User\TodoSubItem;
use Polis\Models\User\TodoTaskNode;
use Polis\Models\User\UserPageComponent;

/**
 * Todo-specific tree operations. Generic mechanics (hydrate from JSON, serialize
 * to JSON, re-order, relocate a sub-tree) are delegated to the domain-agnostic
 * {@see NodeTreeServiceContract} via a {@see TodoNodeTreeCodec}. What remains
 * here is genuinely Todo-specific: the carry-forward copy that syncs hour
 * balances / accumulates deficit, and the UserPageComponent-aware node move that
 * spawns/collapses components.
 */
class TodoTaskTreeService
{
    protected TodoNodeTreeCodec $codec;

    public function __construct(
        protected NodeTreeServiceContract $nodeTree,
    ) {
        $this->codec = new TodoNodeTreeCodec;
    }

    /**
     * Sync a component's config_json tree into relational tables.
     *
     * @param  array<string, mixed>  $configJson
     */
    public function syncFromJson(UserPageComponent $component, array $configJson): void
    {
        $root = $configJson['root'] ?? null;
        if (! $root || ! is_array($root)) {
            return;
        }

        $this->nodeTree->syncFromJson($this->codec, $component->id, $root);
    }

    /**
     * Build the config_json tree ({ root: {...} }) from relational tables.
     *
     * @return array<string, mixed>|null
     */
    public function buildTree(UserPageComponent $component): ?array
    {
        $root = $this->nodeTree->buildTree($this->codec, $component->id);
        if ($root === null) {
            return null;
        }

        return ['root' => $root];
    }

    /**
     * Duplicate relational rows from one component to another, applying Todo
     * carry-forward rules (balance sync, deficit accumulation, completion reset).
     */
    public function copyForwardNodes(UserPageComponent $source, UserPageComponent $target, ?int $targetDayOfWeek = null): void
    {
        $groupLoad = function ($depth = 5) use (&$groupLoad) {
            return function ($q) use ($groupLoad, $depth) {
                $q->orderBy('sort_order');
                if ($depth > 0) {
                    $q->with(['childNodes' => function ($nq) use ($groupLoad, $depth) {
                        $nq->orderBy('sort_order')->with([
                            'groups' => $groupLoad($depth - 1),
                            'subItems',
                            'calendars',
                        ]);
                    }]);
                }
            };
        };

        $rootNode = TodoTaskNode::where('user_page_component_id', $source->id)
            ->whereNull('parent_id')
            ->whereNull('todo_rotating_group_id')
            ->with([
                'children' => function ($q) use ($groupLoad) {
                    $q->orderBy('sort_order')->whereNull('todo_rotating_group_id')->with([
                        'groups' => $groupLoad(),
                        'subItems',
                        'calendars',
                        'children' => function ($cq) use ($groupLoad) {
                            $cq->orderBy('sort_order')->whereNull('todo_rotating_group_id')->with(['groups' => $groupLoad(), 'subItems', 'calendars']);
                        },
                    ]);
                },
                'groups' => $groupLoad(),
                'subItems',
                'calendars',
            ])
            ->first();

        if (! $rootNode) {
            return;
        }

        DB::transaction(function () use ($target, $rootNode, $targetDayOfWeek) {
            $this->copyNode($target->id, null, $rootNode, 0, $targetDayOfWeek);
        });
    }

    protected function copyNode(int $componentId, ?int $parentId, TodoTaskNode $source, int $sortOrder, ?int $targetDayOfWeek): TodoTaskNode
    {
        $onCopy = $source->on_copy ?? 'increment';
        $schedule = $source->schedule ?? [0, 1, 2, 3, 4, 5, 6];
        $isScheduled = $targetDayOfWeek === null || in_array($targetDayOfWeek, $schedule, true);
        $trackingMode = $source->tracking_mode ?? 'units';

        $tally = $source->tally;
        // For hours-mode: sync tally from the authoritative balance record
        if ($trackingMode === 'hours' && $source->todo_balance_id) {
            $balance = TodoBalance::find($source->todo_balance_id);
            if ($balance) {
                $tally = -((float) $balance->balance);
            }
        }
        $loggedHours = (float) $source->logged_hours;
        $loggedTime = (float) $source->logged_time;
        $deficit = (float) $source->deficit;
        $completed = false;

        if ($trackingMode === 'hours') {
            // Don't increment tally here — TodoApplyDailyIncrements handles balance increments
            $loggedHours = 0;
            $loggedTime = 0;
        } else {
            if ($isScheduled && $onCopy === 'increment' && $tally !== null) {
                $tally = $tally + (float) ($source->tally_step ?? 1);
            }

            if ($source->task_type !== TodoTaskNode::TASK_TYPE_CATEGORY || $trackingMode === 'hours') {
                if ($source->time_budget_hours !== null && (float) $source->time_budget_hours > 0) {
                    $tallyMultiplier = $source->tally ?? 1;
                    $budgeted = (float) $tallyMultiplier * (float) $source->time_budget_hours;
                    $logged = (float) $source->logged_hours ?: (float) $source->logged_time;
                    $deficit = $deficit + ($budgeted - $logged);
                }
                $loggedHours = 0;
                $loggedTime = 0;
            }
        }

        $node = TodoTaskNode::create([
            'user_page_component_id' => $componentId,
            'parent_id' => $parentId,
            'sort_order' => $sortOrder,
            'client_id' => $source->client_id,
            'task_type' => $source->task_type,
            'label' => $source->label,
            'description' => $source->description,
            'collapsed' => $source->collapsed,
            'tally' => $tally,
            'tally_step' => $source->tally_step,
            'schedule' => $source->schedule,
            'on_copy' => $source->on_copy,
            'time_budget_hours' => $source->time_budget_hours,
            'logged_hours' => $loggedHours,
            'logged_time' => $loggedTime,
            'deficit' => $deficit,
            'tracking_mode' => $source->tracking_mode,
            'decrement_on_done' => $source->decrement_on_done,
            'time_tracking_mode' => $source->time_tracking_mode,
            'todo_balance_id' => $source->todo_balance_id,
            'completed' => $completed,
            'last_date' => $source->last_date,
            'custom_groups' => $source->custom_groups,
            'cascade_ratio' => $source->cascade_ratio,
            'show_checkmark' => $source->show_checkmark,
            // Rotation-slot cycle count is ALWAYS preserved verbatim — never routed through the
            // node's on_copy tally rule (an 'increment' slot would otherwise gain a phantom
            // completion every midnight and corrupt the rotation focus).
            'count_this_group' => $source->count_this_group,
        ]);

        // Copy calendar associations (junction table — persists schedule overrides across days)
        $calendarSyncData = [];
        foreach ($source->calendars as $cal) {
            $calendarSyncData[$cal->id] = [
                'mode' => $cal->pivot->mode,
                'sort_order' => $cal->pivot->sort_order,
            ];
        }
        if (! empty($calendarSyncData)) {
            $node->calendars()->sync($calendarSyncData);
        }

        // Copy children
        foreach ($source->children as $idx => $child) {
            $this->copyNode($componentId, $node->id, $child, $idx, $targetDayOfWeek);
        }

        // Copy groups
        foreach ($source->groups as $idx => $group) {
            $this->copyGroup($componentId, $node->id, null, $group, $idx, $targetDayOfWeek);
        }

        // Copy sub_items with reset completion
        foreach ($source->subItems as $idx => $subItem) {
            TodoSubItem::create([
                'todo_task_node_id' => $node->id,
                'client_id' => $subItem->client_id,
                'text' => $subItem->text,
                'completed' => false,
                'sort_order' => $idx,
            ]);
        }

        return $node;
    }

    protected function copyGroup(int $componentId, int $nodeId, ?int $parentGroupId, TodoRotatingGroup $source, int $sortOrder, ?int $targetDayOfWeek = null): TodoRotatingGroup
    {
        $groupOnCopy = $source->on_copy ?? 'preserve';
        $countThisGroup = match ($groupOnCopy) {
            'increment' => $source->count_this_group + 1,
            'preserve' => $source->count_this_group,
            default => 0,
        };

        $group = TodoRotatingGroup::create([
            'todo_task_node_id' => $nodeId,
            'group_number' => $source->group_number,
            'label' => $source->label,
            'count_this_group' => $countThisGroup,
            'on_copy' => $source->on_copy,
            'last_date' => $source->last_date,
            'mark_done_on_group' => $source->mark_done_on_group,
            'cascade_ratio' => $source->cascade_ratio,
            'sort_order' => $sortOrder,
        ]);

        // Copy child nodes within the group
        foreach ($source->childNodes as $idx => $childNode) {
            $copiedNode = $this->copyNode($componentId, null, $childNode, $idx, $targetDayOfWeek);
            $copiedNode->update(['todo_rotating_group_id' => $group->id, 'parent_id' => null]);
        }

        return $group;
    }

    /**
     * Move a node between components (or to a new component, or within the same
     * component to a new parent).
     *
     * @return array{source: UserPageComponent, target: UserPageComponent}
     */
    public function moveNode(
        string $nodeClientId,
        int $sourceComponentId,
        ?int $targetComponentId,
        ?string $targetParentClientId,
        int $targetSortOrder,
        int $pageId,
        int $userId,
    ): array {
        return DB::transaction(function () use ($nodeClientId, $sourceComponentId, $targetComponentId, $targetParentClientId, $targetSortOrder, $pageId) {
            $node = TodoTaskNode::where('user_page_component_id', $sourceComponentId)
                ->where('client_id', $nodeClientId)
                ->firstOrFail();

            $sourceComponent = UserPageComponent::findOrFail($sourceComponentId);

            if ($targetComponentId === null) {
                $maxOrder = UserPageComponent::where('user_page_id', $pageId)->max('display_order') ?? 0;
                $targetComponent = UserPageComponent::create([
                    'user_page_id' => $pageId,
                    'component_type' => 'todo_task',
                    'display_order' => $maxOrder + 1,
                    'config_json' => [],
                ]);
            } else {
                $targetComponent = UserPageComponent::findOrFail($targetComponentId);
            }

            $targetParentId = null;
            if ($targetParentClientId) {
                $targetParent = TodoTaskNode::where('user_page_component_id', $targetComponent->id)
                    ->where('client_id', $targetParentClientId)
                    ->first();
                $targetParentId = $targetParent?->id;
            }

            // Generic tree relocation: moves the node + its whole sub-tree into
            // the target component/parent and renumbers the vacated siblings.
            $this->nodeTree->moveNode($node, $targetComponent->id, $targetParentId, $targetSortOrder);

            // If source component has no more root nodes, promote any orphaned
            // children to their own components, then delete the empty source.
            if ($sourceComponentId !== $targetComponent->id) {
                $remainingRoots = TodoTaskNode::where('user_page_component_id', $sourceComponentId)
                    ->whereNull('parent_id')
                    ->count();
                if ($remainingRoots === 0) {
                    $orphans = TodoTaskNode::where('user_page_component_id', $sourceComponentId)
                        ->whereNotNull('parent_id')
                        ->whereDoesntHave('parent')
                        ->get();
                    $maxOrder = UserPageComponent::where('user_page_id', $sourceComponent->user_page_id)->max('display_order') ?? 0;
                    foreach ($orphans as $idx => $orphan) {
                        $newComp = UserPageComponent::create([
                            'user_page_id' => $sourceComponent->user_page_id,
                            'component_type' => 'todo_task',
                            'display_order' => $maxOrder + $idx + 1,
                            'config_json' => [],
                        ]);
                        $this->nodeTree->moveNode($orphan, $newComp->id, null, 0);
                    }
                    $sourceComponent->delete();
                }
            }

            // Rebuild config_json for both components
            $sourceComponent->refresh();
            $targetComponent->refresh();

            $sourceTree = $this->buildTree($sourceComponent);
            $sourceComponent->updateQuietly(['config_json' => $sourceTree ?? []]);

            if ($sourceComponentId !== $targetComponent->id) {
                $targetTree = $this->buildTree($targetComponent);
                $targetComponent->updateQuietly(['config_json' => $targetTree ?? []]);
            }

            return [
                'source' => $sourceComponent,
                'target' => $targetComponent,
            ];
        });
    }
}
