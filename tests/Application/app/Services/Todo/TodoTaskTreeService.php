<?php

declare(strict_types=1);

namespace App\Services\Todo;

use App\Models\User\TodoBalance;
use App\Models\User\TodoBalanceLog;
use App\Models\User\TodoRotatingGroup;
use App\Models\User\TodoSubItem;
use App\Models\User\TodoTaskNode;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Polis\Models\User\UserPageComponent;

class TodoTaskTreeService
{
    /**
     * Sync a component's config_json tree into relational tables.
     * Deletes all existing rows for the component and re-creates from JSON.
     */
    public function syncFromJson(UserPageComponent $component, array $configJson): void
    {
        $root = $configJson['root'] ?? null;
        if (! $root || ! is_array($root)) {
            return;
        }

        DB::transaction(function () use ($component, $root) {
            // Delete existing nodes (cascade handles groups, items, sub_items)
            TodoTaskNode::where('user_page_component_id', $component->id)
                ->whereNull('parent_id')
                ->each(function (TodoTaskNode $node) {
                    $node->forceDelete();
                });

            // Also clean up any orphaned nodes
            TodoTaskNode::where('user_page_component_id', $component->id)->forceDelete();

            $this->createNodeFromJson($component->id, null, $root, 0);
        });
    }

    /**
     * Recursively create a TodoTaskNode (and children/groups/sub_items) from JSON.
     */
    protected function createNodeFromJson(int $componentId, ?int $parentId, array $data, int $sortOrder): TodoTaskNode
    {
        $node = TodoTaskNode::create([
            'user_page_component_id' => $componentId,
            'parent_id' => $parentId,
            'sort_order' => $sortOrder,
            'client_id' => $data['id'] ?? ('tn-' . time() . '-' . substr(md5((string) rand()), 0, 6)),
            'task_type' => $data['task_type'] ?? 'line_item',
            'label' => $data['label'] ?? '',
            'description' => $data['description'] ?? null,
            'collapsed' => (bool) ($data['collapsed'] ?? false),
            'tally' => $data['tally'] ?? null,
            'tally_step' => $data['tally_step'] ?? 1,
            'schedule' => $data['schedule'] ?? null,
            'on_copy' => $data['on_copy'] ?? 'increment',
            'time_budget_hours' => $data['time_budget_hours'] ?? null,
            'logged_hours' => (float) ($data['logged_hours'] ?? 0),
            'logged_time' => (float) ($data['logged_time'] ?? 0),
            'deficit' => (float) ($data['deficit'] ?? 0),
            'tracking_mode' => $data['tracking_mode'] ?? 'units',
            'decrement_on_done' => (bool) ($data['decrement_on_done'] ?? true),
            'time_tracking_mode' => $data['time_tracking_mode'] ?? 'reset',
            'todo_balance_id' => $data['todo_balance_id'] ?? null,
            'completed' => (bool) ($data['completed'] ?? false),
            'last_date' => $data['last_date'] ?? null,
            'custom_groups' => (bool) ($data['custom_groups'] ?? false),
            'cascade_ratio' => (int) ($data['cascade_ratio'] ?? 2),
            'show_checkmark' => (bool) ($data['show_checkmark'] ?? false),
            'count_this_group' => isset($data['count_this_group']) ? (int) $data['count_this_group'] : null,
        ]);

        // Recurse into children (categories, rotating slots, priority_group items)
        if (! empty($data['children']) && is_array($data['children'])) {
            foreach ($data['children'] as $idx => $child) {
                $this->createNodeFromJson($componentId, $node->id, $child, $idx);
            }
        }

        // Create rotating groups
        if (! empty($data['groups']) && is_array($data['groups'])) {
            foreach ($data['groups'] as $idx => $groupData) {
                $this->createGroupFromJson($node->id, null, $groupData, $idx);
            }
        }

        // Create sub_items (line_item nodes)
        if (! empty($data['sub_items']) && is_array($data['sub_items'])) {
            foreach ($data['sub_items'] as $idx => $subItemData) {
                TodoSubItem::create([
                    'todo_task_node_id' => $node->id,
                    'client_id' => $subItemData['id'] ?? ('si-' . time() . '-' . substr(md5((string) rand()), 0, 6)),
                    'text' => $subItemData['text'] ?? '',
                    'completed' => (bool) ($subItemData['completed'] ?? false),
                    'sort_order' => $idx,
                ]);
            }
        }

        return $node;
    }

    /**
     * Create a rotating group (and its items/sub_groups) from JSON.
     */
    protected function createGroupFromJson(int $nodeId, ?int $parentGroupId, array $data, int $sortOrder): TodoRotatingGroup
    {
        $group = TodoRotatingGroup::create([
            'todo_task_node_id' => $nodeId,
            'group_number' => $data['group_number'] ?? $sortOrder,
            'label' => $data['label'] ?? null,
            'count_this_group' => (int) ($data['count_this_group'] ?? 0),
            'on_copy' => $data['on_copy'] ?? 'preserve',
            'last_date' => $data['last_date'] ?? null,
            'mark_done_on_group' => (bool) ($data['mark_done_on_group'] ?? false),
            'cascade_ratio' => (int) ($data['cascade_ratio'] ?? 2),
            'sort_order' => $sortOrder,
        ]);

        // Create child nodes within the group
        $children = $data['children'] ?? $data['items'] ?? [];
        if (! empty($children) && is_array($children)) {
            // Get component ID from the parent node
            $parentNode = TodoTaskNode::find($nodeId);
            $compId = $parentNode ? $parentNode->user_page_component_id : 0;
            foreach ($children as $idx => $childData) {
                $childNode = $this->createNodeFromJson($compId, null, $childData, $idx);
                $childNode->update(['todo_rotating_group_id' => $group->id, 'parent_id' => null]);
            }
        }

        return $group;
    }

    /**
     * Build the config_json tree from relational tables.
     * Returns the full config_json structure: { root: {...} }
     */
    public function buildTree(UserPageComponent $component): ?array
    {
        $rootNode = TodoTaskNode::where('user_page_component_id', $component->id)
            ->whereNull('parent_id')
            ->whereNull('todo_rotating_group_id')
            ->with($this->treeLoad(8))
            ->first();

        if (! $rootNode) {
            return null;
        }

        return [
            'root' => $this->nodeToJson($rootNode),
        ];
    }

    /**
     * One self-recursive eager-load spec for the whole tree: children (the real tree, including
     * priority_group slots) plus legacy rotating groups at every level (until the groups cleanup).
     * Depth 8 covers e.g. root→category→category→rotating→priority_group→rotating→priority_group→
     * line_item; anything deeper falls back to lazy loading (a perf concern, not correctness).
     */
    protected function treeLoad(int $depth): array
    {
        $base = ['subItems', 'calendars'];
        if ($depth <= 0) {
            return $base;
        }

        return array_merge($base, [
            'children' => function ($q) use ($depth) {
                $q->orderBy('sort_order')
                    ->whereNull('todo_rotating_group_id')
                    ->with($this->treeLoad($depth - 1));
            },
            'groups' => function ($q) use ($depth) {
                $q->orderBy('sort_order')->with(['childNodes' => function ($nq) use ($depth) {
                    $nq->orderBy('sort_order')->with($this->treeLoad($depth - 1));
                }]);
            },
        ]);
    }

    /**
     * Convert a TodoTaskNode model to the JSON structure the frontend expects.
     */
    protected function nodeToJson(TodoTaskNode $node): array
    {
        $json = [
            'id' => $node->client_id,
            'task_type' => $node->task_type,
            'label' => $node->label,
        ];

        // Always include on_copy (frontend always sends it)
        $json['on_copy'] = $node->on_copy;

        // Optional fields — include if non-default
        if ($node->description !== null) {
            $json['description'] = $node->description;
        }
        if ($node->collapsed) {
            $json['collapsed'] = true;
        }
        if ($node->tally !== null) {
            $json['tally'] = (float) $node->tally;
        }
        if ($node->tally_step !== null) {
            $json['tally_step'] = (float) $node->tally_step;
        }
        if ($node->schedule !== null) {
            $json['schedule'] = $node->schedule;
        }
        if ($node->time_budget_hours !== null) {
            $json['time_budget_hours'] = (float) $node->time_budget_hours;
        }
        // Always include logged_hours/logged_time when tracking is configured
        if ($node->tracking_mode !== 'units' || $node->time_budget_hours !== null || (float) $node->logged_hours !== 0.0) {
            $json['logged_hours'] = (float) $node->logged_hours;
        }
        if ((float) $node->logged_time !== 0.0 || $node->task_type === TodoTaskNode::TASK_TYPE_ROTATING) {
            $json['logged_time'] = (float) $node->logged_time;
        }
        if ((float) $node->deficit !== 0.0) {
            $json['deficit'] = (float) $node->deficit;
        }
        // Always serialize tracking_mode. The frontend applies a shared default (getTrackingMode)
        // when it's absent, and that default is 'hours' — so omitting 'units' here would make the
        // frontend mis-treat units nodes as hours. Send it explicitly so both sides agree.
        $json['tracking_mode'] = $node->tracking_mode;
        if (! $node->decrement_on_done) {
            $json['decrement_on_done'] = false;
        }
        if ($node->show_checkmark) {
            $json['show_checkmark'] = true;
        }
        if ($node->time_tracking_mode !== 'reset') {
            $json['time_tracking_mode'] = $node->time_tracking_mode;
        }
        if ($node->todo_balance_id !== null) {
            $json['todo_balance_id'] = $node->todo_balance_id;
        }

        // Calendar rules
        if ($node->relationLoaded('calendars') && $node->calendars->isNotEmpty()) {
            $json['calendar_rules'] = $node->calendars->map(fn ($cal) => [
                'calendar_id' => $cal->id,
                'calendar_name' => $cal->name,
                'mode' => $cal->pivot->mode,
            ])->toArray();
        }

        // Rotation-slot cycle count (any direct child of a rotating node can carry one)
        if ($node->count_this_group !== null) {
            $json['count_this_group'] = (int) $node->count_this_group;
        }

        // Type-specific fields. Container types serialize their children — for rotating nodes the
        // children ARE the slots (priority_group / bare task / nested rotating) post-migration.
        $containerTypes = [
            TodoTaskNode::TASK_TYPE_CATEGORY,
            TodoTaskNode::TASK_TYPE_ROTATING,
            TodoTaskNode::TASK_TYPE_PRIORITY_GROUP,
        ];
        if (in_array($node->task_type, $containerTypes, true)) {
            $json['children'] = $node->children->map(fn (TodoTaskNode $child) => $this->nodeToJson($child))->toArray();
        }

        if ($node->task_type === TodoTaskNode::TASK_TYPE_ROTATING) {
            if ($node->cascade_ratio !== 2) {
                $json['cascade_ratio'] = $node->cascade_ratio;
            }
            // Legacy groups: emitted only while un-migrated rows exist (shape-follows-data).
            // custom_groups rides along for the pre-cutover frontend and retires with them.
            if ($node->groups->isNotEmpty()) {
                if ($node->custom_groups) {
                    $json['custom_groups'] = true;
                }
                $json['groups'] = $node->groups->values()->map(fn (TodoRotatingGroup $group) => $this->groupToJson($group))->toArray();
            }
        }

        if ($node->completed) {
            $json['completed'] = true;
        }
        if ($node->last_date !== null) {
            $json['last_date'] = $node->last_date;
        }
        if ($node->subItems->isNotEmpty()) {
            $json['sub_items'] = $node->subItems->map(fn (TodoSubItem $si) => [
                'id' => $si->client_id,
                'text' => $si->text,
                'completed' => $si->completed,
            ])->toArray();
        }

        return $json;
    }

    /**
     * Convert a TodoRotatingGroup model to JSON.
     */
    protected function groupToJson(TodoRotatingGroup $group): array
    {
        $json = [
            'group_number' => $group->group_number,
            'count_this_group' => $group->count_this_group,
            // Child nodes replace the old items/sub_groups — each child is a full TodoTaskNode
            'children' => $group->childNodes->map(fn (TodoTaskNode $child) => $this->nodeToJson($child))->toArray(),
        ];

        if ($group->label !== null) {
            $json['label'] = $group->label;
        }
        if ($group->on_copy !== 'preserve') {
            $json['on_copy'] = $group->on_copy;
        }
        if ($group->last_date !== null) {
            $json['last_date'] = $group->last_date;
        }
        if ($group->mark_done_on_group) {
            $json['mark_done_on_group'] = true;
        }
        if ($group->cascade_ratio !== 2) {
            $json['cascade_ratio'] = $group->cascade_ratio;
        }

        return $json;
    }

    /**
     * Duplicate relational rows from one component to another, applying copy rules.
     * Used by TodoGenerationService during copy-forward.
     */
    public function copyForwardNodes(UserPageComponent $source, UserPageComponent $target, ?int $targetDayOfWeek = null): void
    {
        $rootNode = TodoTaskNode::where('user_page_component_id', $source->id)
            ->whereNull('parent_id')
            ->whereNull('todo_rotating_group_id')
            ->with($this->treeLoad(8))
            ->first();

        if (! $rootNode) {
            return;
        }

        DB::transaction(function () use ($target, $rootNode, $targetDayOfWeek) {
            $this->copyNode($target->id, null, $rootNode, 0, $targetDayOfWeek);
        });
    }

    /**
     * Copy a node with copy rules applied.
     */
    protected function copyNode(int $componentId, ?int $parentId, TodoTaskNode $source, int $sortOrder, ?int $targetDayOfWeek): TodoTaskNode
    {
        $onCopy = $source->on_copy ?? 'increment';
        $schedule = $source->schedule ?? [0, 1, 2, 3, 4, 5, 6];
        $isScheduled = $targetDayOfWeek === null || in_array($targetDayOfWeek, $schedule, true);
        $trackingMode = $source->tracking_mode ?? 'units';

        $tally = $source->tally;
        // For hours-mode: sync tally from the authoritative balance record
        if ($trackingMode === 'hours' && $source->todo_balance_id) {
            $balance = \App\Models\User\TodoBalance::find($source->todo_balance_id);
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
            // (incrementing tally here would double-count since the balance is already incremented by the cron)
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

    /**
     * Copy a rotating group with copy rules applied.
     */
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

        // Copy items with copy rules
        // Copy child nodes within the group
        foreach ($source->childNodes as $idx => $childNode) {
            $copiedNode = $this->copyNode($componentId, null, $childNode, $idx, $targetDayOfWeek);
            $copiedNode->update(['todo_rotating_group_id' => $group->id, 'parent_id' => null]);
        }

        return $group;
    }

    /**
     * Move a node between components (or to a new component, or within the same component to a new parent).
     *
     * @return array{source: UserPageComponent, target: UserPageComponent} The updated components
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
        return DB::transaction(function () use ($nodeClientId, $sourceComponentId, $targetComponentId, $targetParentClientId, $targetSortOrder, $pageId, $userId) {
            // Find the source node
            $node = TodoTaskNode::where('user_page_component_id', $sourceComponentId)
                ->where('client_id', $nodeClientId)
                ->firstOrFail();

            $sourceComponent = UserPageComponent::findOrFail($sourceComponentId);

            // Determine target component
            if ($targetComponentId === null) {
                // Create a new component for this node (extracting to top level)
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

            // Determine target parent node
            $targetParentId = null;
            if ($targetParentClientId) {
                $targetParent = TodoTaskNode::where('user_page_component_id', $targetComponent->id)
                    ->where('client_id', $targetParentClientId)
                    ->first();
                $targetParentId = $targetParent?->id;

                // Cycle guard: refuse to move a node under itself or its own descendant —
                // moveDescendants would recurse forever (observed: a drag combined with a row
                // inside the dragged card's own subtree, crashing the request mid-transaction).
                $cursor = $targetParent;
                while ($cursor) {
                    if ($cursor->id === $node->id) {
                        throw new \InvalidArgumentException('Cannot move a node into its own subtree.');
                    }
                    $cursor = $cursor->parent_id ? TodoTaskNode::find($cursor->parent_id) : null;
                }
            }

            // Move the node and all its descendants
            $this->moveNodeTree($node, $targetComponent->id, $targetParentId, $targetSortOrder);

            // Reorder siblings in the source parent
            $this->reorderSiblings($sourceComponentId, $node->parent_id);

            // If source component has no more root nodes, promote any orphaned
            // children to their own components, then delete the empty source
            if ($sourceComponentId !== $targetComponent->id) {
                $remainingRoots = TodoTaskNode::where('user_page_component_id', $sourceComponentId)
                    ->whereNull('parent_id')
                    ->count();
                if ($remainingRoots === 0) {
                    // Check for orphaned non-root nodes still in this component
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
                        $this->moveNodeTree($orphan, $newComp->id, null, 0);
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

    /**
     * Move a node and all its descendants to a new component/parent.
     */
    protected function moveNodeTree(TodoTaskNode $node, int $targetComponentId, ?int $targetParentId, int $sortOrder): void
    {
        $node->update([
            'user_page_component_id' => $targetComponentId,
            'parent_id' => $targetParentId,
            'sort_order' => $sortOrder,
        ]);

        // Move all descendant nodes to the new component
        $this->moveDescendants($node, $targetComponentId);
    }

    /**
     * Recursively move descendant nodes to a new component.
     */
    protected function moveDescendants(TodoTaskNode $node, int $targetComponentId): void
    {
        $children = TodoTaskNode::where('parent_id', $node->id)->get();
        foreach ($children as $child) {
            $child->updateQuietly(['user_page_component_id' => $targetComponentId]);
            $this->moveDescendants($child, $targetComponentId);
        }
    }

    /**
     * Reorder siblings after a node is removed.
     */
    protected function reorderSiblings(int $componentId, ?int $parentId): void
    {
        $siblings = TodoTaskNode::where('user_page_component_id', $componentId)
            ->where('parent_id', $parentId)
            ->orderBy('sort_order')
            ->get();

        foreach ($siblings as $idx => $sibling) {
            if ($sibling->sort_order !== $idx) {
                $sibling->updateQuietly(['sort_order' => $idx]);
            }
        }
    }
}
