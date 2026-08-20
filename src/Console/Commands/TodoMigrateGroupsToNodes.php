<?php

declare(strict_types=1);

namespace Polis\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Polis\Models\User\TodoRotatingGroup;
use Polis\Models\User\TodoTaskNode;

/**
 * Convert every live TodoRotatingGroup into ordinary child nodes of its rotating node,
 * unifying priority groups into the task tree:
 *
 *  (b) COLLAPSE — a group whose only child is a rotating node with a matching (or default)
 *      label: the rotating child becomes the slot directly, inheriting the group's rotation
 *      fields. Kills the redundant "group named X containing rotating named X" nesting.
 *  (a) GROUP    — a group with children becomes a `priority_group` node; children re-parented.
 *  (c) EMPTY    — an empty group becomes a bare line_item slot with a mark-done checkmark.
 *
 * Converted group rows are SOFT-deleted: that is the idempotency marker, it stops the legacy
 * `groups` serialization (shape-follows-data cutover), and it keeps the rows as rollback data.
 * Order matters: children are re-parented and their FK nulled BEFORE the group is deleted —
 * the todo_task_nodes.todo_rotating_group_id FK cascades on (hard) delete.
 */
class TodoMigrateGroupsToNodes extends Command
{
    protected $signature = 'todo:migrate-groups-to-nodes {--dry-run : Classify and report without writing} {--component= : Limit to one user_page_component id}';

    protected $description = 'Convert rotating priority groups into priority_group/task child nodes';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $componentId = $this->option('component') ? (int) $this->option('component') : null;

        $groupsQuery = TodoRotatingGroup::query()->orderBy('todo_task_node_id')->orderBy('sort_order')->orderBy('group_number');
        if ($componentId !== null) {
            $groupsQuery->whereHas('taskNode', fn ($q) => $q->where('user_page_component_id', $componentId));
        }

        $byNode = $groupsQuery->get()->groupBy('todo_task_node_id');
        $counts = ['group' => 0, 'collapse' => 0, 'empty' => 0, 'skipped_no_node' => 0];
        $warnings = [];

        foreach ($byNode as $nodeId => $groups) {
            $rotating = TodoTaskNode::find($nodeId);
            if (! $rotating) {
                // Orphaned group rows (audit found none, but never crash the run over one)
                $counts['skipped_no_node'] += $groups->count();
                $warnings[] = "groups for missing node {$nodeId} skipped";

                continue;
            }

            $work = function () use ($rotating, $groups, &$counts, &$warnings, $dryRun) {
                foreach ($groups->values() as $i => $group) {
                    $children = TodoTaskNode::where('todo_rotating_group_id', $group->id)
                        ->orderBy('sort_order')
                        ->get();

                    $rule = $this->classify($group, $children);
                    $counts[$rule]++;

                    if ($rule === 'collapse' && $children->first()->on_copy !== 'preserve') {
                        $warnings[] = "collapse: child '{$children->first()->label}' (group {$group->id}) has on_copy={$children->first()->on_copy}";
                    }

                    if ($dryRun) {
                        continue;
                    }

                    if ($rule === 'collapse') {
                        $child = $children->first();
                        $child->update([
                            'parent_id' => $rotating->id,
                            'todo_rotating_group_id' => null,
                            'sort_order' => $i,
                            'count_this_group' => $group->count_this_group,
                            'last_date' => $group->last_date ?? $child->last_date,
                            'show_checkmark' => (bool) $group->mark_done_on_group,
                        ]);
                    } elseif ($rule === 'group') {
                        $slot = TodoTaskNode::create([
                            'user_page_component_id' => $rotating->user_page_component_id,
                            'parent_id' => $rotating->id,
                            'sort_order' => $i,
                            // Deterministic id: stable across re-runs and day-copies
                            'client_id' => 'pg-'.$group->id,
                            'task_type' => TodoTaskNode::TASK_TYPE_PRIORITY_GROUP,
                            'label' => $group->label ?? 'Priority',
                            'count_this_group' => $group->count_this_group,
                            'last_date' => $group->last_date,
                            'show_checkmark' => (bool) $group->mark_done_on_group,
                            'cascade_ratio' => (int) ($group->cascade_ratio ?? 2),
                            'on_copy' => 'preserve',
                            'tally_step' => 0,
                        ]);
                        foreach ($children as $child) {
                            $child->update([
                                'parent_id' => $slot->id,
                                'todo_rotating_group_id' => null,
                            ]);
                        }
                    } else { // empty
                        TodoTaskNode::create([
                            'user_page_component_id' => $rotating->user_page_component_id,
                            'parent_id' => $rotating->id,
                            'sort_order' => $i,
                            'client_id' => 'pg-'.$group->id,
                            'task_type' => TodoTaskNode::TASK_TYPE_LINE_ITEM,
                            'label' => $group->label ?? 'Priority',
                            'count_this_group' => $group->count_this_group,
                            'last_date' => $group->last_date,
                            'show_checkmark' => true,
                            'on_copy' => 'preserve',
                            'tally_step' => 0,
                        ]);
                    }

                    // Children are re-homed and FKs nulled — now the marker delete is safe.
                    $group->delete();
                }
            };

            $dryRun ? $work() : DB::transaction($work);
        }

        foreach ($warnings as $w) {
            $this->warn($w);
        }
        $this->info(sprintf(
            '%s — priority_group: %d, collapsed: %d, empty→task: %d, skipped: %d (nodes: %d)',
            $dryRun ? 'DRY RUN' : 'Converted',
            $counts['group'], $counts['collapse'], $counts['empty'], $counts['skipped_no_node'], $byNode->count()
        ));

        if (! $dryRun) {
            // Post-checks: fail loudly if anything survived.
            $liveGroups = TodoRotatingGroup::query()
                ->when($componentId !== null, fn ($q) => $q->whereHas('taskNode', fn ($n) => $n->where('user_page_component_id', $componentId)))
                ->count();
            $danglingFks = TodoTaskNode::whereNotNull('todo_rotating_group_id')
                ->when($componentId !== null, fn ($q) => $q->where('user_page_component_id', $componentId))
                ->count();

            if ($liveGroups > 0 || $danglingFks > 0) {
                $this->error("POST-CHECK FAILED: live groups={$liveGroups}, dangling FKs={$danglingFks}");

                return self::FAILURE;
            }
            $this->info('Post-checks passed: 0 live groups, 0 dangling group FKs.');
        }

        return self::SUCCESS;
    }

    /**
     * @param  Collection<int, TodoTaskNode>  $children
     */
    protected function classify(TodoRotatingGroup $group, $children): string
    {
        if ($children->isEmpty()) {
            return 'empty';
        }

        if ($children->count() === 1 && $children->first()->task_type === TodoTaskNode::TASK_TYPE_ROTATING) {
            $child = $children->first();
            $label = $group->label;
            $isDefaultLabel = $label === null
                || $label === 'Priority'
                || preg_match('/^#\d+ Priority$/', $label) === 1;
            if ($isDefaultLabel || $label === $child->label) {
                return 'collapse';
            }
        }

        return 'group';
    }
}
