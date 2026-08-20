<?php

declare(strict_types=1);

namespace Polis\Services\Todo;

use Illuminate\Database\Eloquent\Model;
use Polis\Contracts\Services\Relations\NodeTreeCodecContract;
use Polis\Models\User\TodoRotatingGroup;
use Polis\Models\User\TodoSubItem;
use Polis\Models\User\TodoTaskNode;
use Polis\Services\Relations\NodeTreeService;

/**
 * The Todo domain's implementation of {@see NodeTreeCodecContract}. It teaches
 * the generic {@see NodeTreeService} everything
 * Todo-specific: which columns a node carries, its side-relations (rotating
 * groups, sub-items, calendar pivots) and its JSON shape — so all todo column
 * knowledge stays in the Todo layer and the tree engine stays generic.
 *
 * @implements NodeTreeCodecContract<TodoTaskNode>
 */
class TodoNodeTreeCodec implements NodeTreeCodecContract
{
    public function nodeClass(): string
    {
        return TodoTaskNode::class;
    }

    public function childrenKey(): string
    {
        return 'children';
    }

    public function attributesFromJson(array $data, array $position): array
    {
        return [
            'user_page_component_id' => $position['scope'],
            'parent_id' => $position['parent_id'],
            'sort_order' => $position['sort_order'],
            'client_id' => $data['id'] ?? ('tn-'.time().'-'.substr(md5((string) rand()), 0, 6)),
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
        ];
    }

    /**
     * @param  TodoTaskNode  $node
     */
    public function syncSideRelations(Model $node, array $data): void
    {
        // Rotating groups (each group's child nodes are created recursively)
        if (! empty($data['groups']) && is_array($data['groups'])) {
            foreach (array_values($data['groups']) as $idx => $groupData) {
                if (is_array($groupData)) {
                    $this->createGroupFromJson($node, $groupData, $idx);
                }
            }
        }

        // Sub-items
        if (! empty($data['sub_items']) && is_array($data['sub_items'])) {
            foreach (array_values($data['sub_items']) as $idx => $subItemData) {
                if (! is_array($subItemData)) {
                    continue;
                }
                TodoSubItem::create([
                    'todo_task_node_id' => $node->id,
                    'client_id' => $subItemData['id'] ?? ('si-'.time().'-'.substr(md5((string) rand()), 0, 6)),
                    'text' => $subItemData['text'] ?? '',
                    'completed' => (bool) ($subItemData['completed'] ?? false),
                    'sort_order' => $idx,
                ]);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function createGroupFromJson(TodoTaskNode $node, array $data, int $sortOrder): TodoRotatingGroup
    {
        $group = TodoRotatingGroup::create([
            'todo_task_node_id' => $node->id,
            'group_number' => $data['group_number'] ?? $sortOrder,
            'label' => $data['label'] ?? null,
            'count_this_group' => (int) ($data['count_this_group'] ?? 0),
            'on_copy' => $data['on_copy'] ?? 'preserve',
            'last_date' => $data['last_date'] ?? null,
            'mark_done_on_group' => (bool) ($data['mark_done_on_group'] ?? false),
            'cascade_ratio' => (int) ($data['cascade_ratio'] ?? 2),
            'sort_order' => $sortOrder,
        ]);

        $children = $data['children'] ?? $data['items'] ?? [];
        if (! empty($children) && is_array($children)) {
            foreach (array_values($children) as $idx => $childData) {
                if (! is_array($childData)) {
                    continue;
                }
                $childNode = TodoTaskNode::create($this->attributesFromJson($childData, [
                    'scope' => $node->user_page_component_id,
                    'parent_id' => null,
                    'sort_order' => $idx,
                ]));
                $this->syncSideRelations($childNode, $childData);
                $childNode->update(['todo_rotating_group_id' => $group->id, 'parent_id' => null]);
            }
        }

        return $group;
    }

    /**
     * @param  TodoTaskNode  $node
     */
    public function nodeToJson(Model $node, callable $serializeChild): array
    {
        $json = [
            'id' => $node->client_id,
            'task_type' => $node->task_type,
            'label' => $node->label,
        ];

        // Always include on_copy (frontend always sends it)
        $json['on_copy'] = $node->on_copy;

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
            $json['children'] = $node->children->map(fn (TodoTaskNode $child) => $serializeChild($child))->toArray();
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
                $json['groups'] = $node->groups->values()
                    ->map(fn (TodoRotatingGroup $group) => $this->groupToJson($group, $serializeChild))
                    ->toArray();
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
     * @param  callable(TodoTaskNode): array<string, mixed>  $serializeChild
     * @return array<string, mixed>
     */
    protected function groupToJson(TodoRotatingGroup $group, callable $serializeChild): array
    {
        $json = [
            'group_number' => $group->group_number,
            'count_this_group' => $group->count_this_group,
            'children' => $group->childNodes->map(fn (TodoTaskNode $child) => $serializeChild($child))->toArray(),
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

    public function eagerLoad(): array
    {
        return [
            'groups' => fn ($q) => $q->orderBy('sort_order')->with(['childNodes' => fn ($nq) => $nq->orderBy('sort_order')->with(['subItems', 'calendars'])]),
            'subItems',
            'calendars',
        ];
    }
}
