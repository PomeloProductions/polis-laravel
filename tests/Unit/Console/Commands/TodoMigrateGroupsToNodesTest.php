<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Console\Commands;

use Illuminate\Support\Facades\Artisan;
use Polis\Console\Commands\TodoMigrateGroupsToNodes;
use Polis\Models\User\TodoRotatingGroup;
use Polis\Models\User\TodoTaskNode;
use Polis\Tests\TestCase;
use Polis\Tests\Traits\CreatesTodoModuleSchema;

/**
 * todo:migrate-groups-to-nodes — standalone Unit coverage of the three
 * conversion rules and their bookkeeping (the Feature suite exercises the
 * same command end-to-end inside the dummy consumer app):
 *
 *  (a) group with children      → priority_group node, children re-parented;
 *  (b) single-rotating-child + default/matching label → collapse into the child;
 *  (c) empty group              → bare line_item slot with a checkmark.
 *
 * Converted group rows are SOFT-deleted (the idempotency marker), children's
 * todo_rotating_group_id FK is nulled, and post-checks fail the run loudly if
 * any live group or dangling FK survives.
 */
final class TodoMigrateGroupsToNodesTest extends TestCase
{
    use CreatesTodoModuleSchema;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createTodoModuleTables();
        Artisan::registerCommand(new TodoMigrateGroupsToNodes);
    }

    protected function tearDown(): void
    {
        $this->dropTodoModuleTables();
        parent::tearDown();
    }

    private function makeRotating(int $componentId = 1): TodoTaskNode
    {
        return TodoTaskNode::create([
            'user_page_component_id' => $componentId,
            'client_id' => 'rot-'.uniqid(),
            'task_type' => TodoTaskNode::TASK_TYPE_ROTATING,
            'label' => 'Rotating',
        ]);
    }

    private function makeGroup(TodoTaskNode $node, array $attrs = []): TodoRotatingGroup
    {
        return TodoRotatingGroup::create(array_merge([
            'todo_task_node_id' => $node->id,
            'group_number' => 1,
            'label' => 'Priority',
            'count_this_group' => 0,
            'on_copy' => 'preserve',
            'sort_order' => 0,
        ], $attrs));
    }

    private function makeGroupChild(TodoRotatingGroup $group, array $attrs = []): TodoTaskNode
    {
        return TodoTaskNode::create(array_merge([
            'user_page_component_id' => $group->taskNode->user_page_component_id,
            'todo_rotating_group_id' => $group->id,
            'client_id' => 'item-'.uniqid(),
            'task_type' => TodoTaskNode::TASK_TYPE_LINE_ITEM,
            'label' => 'Item',
        ], $attrs));
    }

    public function test_rule_a_group_with_children_becomes_priority_group_node(): void
    {
        $rotating = $this->makeRotating();
        $group = $this->makeGroup($rotating, [
            'label' => 'High',
            'count_this_group' => 3,
            'mark_done_on_group' => true,
            'cascade_ratio' => 4,
            'last_date' => '2026-07-10',
        ]);
        $a = $this->makeGroupChild($group, ['label' => 'A', 'sort_order' => 0]);
        $b = $this->makeGroupChild($group, ['label' => 'B', 'sort_order' => 1]);

        $this->artisan('todo:migrate-groups-to-nodes')->assertExitCode(0);

        // Deterministic client_id makes re-runs and day-copies stable.
        $slot = TodoTaskNode::where('client_id', 'pg-'.$group->id)->first();
        $this->assertNotNull($slot);
        $this->assertSame(TodoTaskNode::TASK_TYPE_PRIORITY_GROUP, $slot->task_type);
        $this->assertSame($rotating->id, $slot->parent_id);
        $this->assertSame('High', $slot->label);
        $this->assertSame(3, $slot->count_this_group);
        $this->assertSame('2026-07-10', $slot->last_date);
        $this->assertTrue($slot->show_checkmark);
        $this->assertSame(4, $slot->cascade_ratio);
        $this->assertSame('preserve', $slot->on_copy);

        foreach ([$a, $b] as $child) {
            $child->refresh();
            $this->assertSame($slot->id, $child->parent_id);
            $this->assertNull($child->todo_rotating_group_id);
        }

        // Soft-delete is the idempotency marker; the row survives as rollback data.
        $this->assertNull(TodoRotatingGroup::find($group->id));
        $this->assertNotNull(TodoRotatingGroup::withTrashed()->find($group->id)->deleted_at);
    }

    public function test_rule_b_single_rotating_child_with_matching_or_default_label_collapses(): void
    {
        $rotating = $this->makeRotating();

        $matching = $this->makeGroup($rotating, [
            'label' => 'Language Study',
            'count_this_group' => -2, // negative cycle counts are legitimate (cascade resets)
            'last_date' => '2026-07-14',
            'mark_done_on_group' => true,
            'sort_order' => 0,
        ]);
        $matchingChild = $this->makeGroupChild($matching, [
            'task_type' => TodoTaskNode::TASK_TYPE_ROTATING,
            'label' => 'Language Study',
        ]);

        $numbered = $this->makeGroup($rotating, ['label' => '#2 Priority', 'sort_order' => 1]);
        $numberedChild = $this->makeGroupChild($numbered, [
            'task_type' => TodoTaskNode::TASK_TYPE_ROTATING,
            'label' => 'Whatever',
        ]);

        $this->artisan('todo:migrate-groups-to-nodes')->assertExitCode(0);

        // The rotating child IS the slot now — no wrapper node is created.
        $matchingChild->refresh();
        $this->assertSame($rotating->id, $matchingChild->parent_id);
        $this->assertNull($matchingChild->todo_rotating_group_id);
        $this->assertSame(-2, $matchingChild->count_this_group);
        $this->assertSame('2026-07-14', $matchingChild->last_date);
        $this->assertTrue($matchingChild->show_checkmark);
        $this->assertSame(0, TodoTaskNode::where('client_id', 'pg-'.$matching->id)->count());

        // "#N Priority" is a default label → collapses even though it differs from the child's.
        $numberedChild->refresh();
        $this->assertSame($rotating->id, $numberedChild->parent_id);
        $this->assertSame(0, TodoTaskNode::where('client_id', 'pg-'.$numbered->id)->count());
    }

    public function test_rule_b_custom_label_keeps_the_wrapper(): void
    {
        $rotating = $this->makeRotating();
        $group = $this->makeGroup($rotating, ['label' => 'Something Custom']);
        $nested = $this->makeGroupChild($group, [
            'task_type' => TodoTaskNode::TASK_TYPE_ROTATING,
            'label' => 'Different Name',
        ]);

        $this->artisan('todo:migrate-groups-to-nodes')->assertExitCode(0);

        // Custom label ≠ child label → keep the wrapper so the label isn't lost.
        $slot = TodoTaskNode::where('client_id', 'pg-'.$group->id)->first();
        $this->assertNotNull($slot);
        $this->assertSame(TodoTaskNode::TASK_TYPE_PRIORITY_GROUP, $slot->task_type);
        $this->assertSame('Something Custom', $slot->label);
        $this->assertSame($slot->id, $nested->refresh()->parent_id);
    }

    public function test_rule_c_empty_group_becomes_bare_line_item_slot(): void
    {
        $rotating = $this->makeRotating();
        $group = $this->makeGroup($rotating, [
            'label' => 'Picked Games',
            'count_this_group' => 2,
            'last_date' => '2026-07-13',
        ]);

        $this->artisan('todo:migrate-groups-to-nodes')->assertExitCode(0);

        $slot = TodoTaskNode::where('client_id', 'pg-'.$group->id)->first();
        $this->assertNotNull($slot);
        $this->assertSame(TodoTaskNode::TASK_TYPE_LINE_ITEM, $slot->task_type);
        $this->assertSame('Picked Games', $slot->label);
        $this->assertSame(2, $slot->count_this_group);
        $this->assertSame('2026-07-13', $slot->last_date);
        $this->assertTrue($slot->show_checkmark);
        $this->assertSame($rotating->id, $slot->parent_id);
        $this->assertNull(TodoRotatingGroup::find($group->id));
    }

    public function test_rerun_is_idempotent_via_soft_delete_marker(): void
    {
        $rotating = $this->makeRotating();
        $group = $this->makeGroup($rotating);
        $this->makeGroupChild($group);

        $this->artisan('todo:migrate-groups-to-nodes')->assertExitCode(0);
        $after = TodoTaskNode::where('parent_id', $rotating->id)->count();

        $this->artisan('todo:migrate-groups-to-nodes')->assertExitCode(0);
        $this->assertSame($after, TodoTaskNode::where('parent_id', $rotating->id)->count());
    }

    public function test_dry_run_classifies_without_writing(): void
    {
        $rotating = $this->makeRotating();
        $group = $this->makeGroup($rotating);
        $child = $this->makeGroupChild($group);

        $this->artisan('todo:migrate-groups-to-nodes', ['--dry-run' => true])->assertExitCode(0);

        $this->assertNotNull(TodoRotatingGroup::find($group->id), 'dry-run must not delete groups');
        $this->assertSame($group->id, $child->refresh()->todo_rotating_group_id);
        $this->assertSame(0, TodoTaskNode::where('client_id', 'pg-'.$group->id)->count());
    }

    public function test_component_option_limits_scope_to_one_component(): void
    {
        $inScope = $this->makeRotating(11);
        $inScopeGroup = $this->makeGroup($inScope);
        $outOfScope = $this->makeRotating(22);
        $outOfScopeGroup = $this->makeGroup($outOfScope);

        $this->artisan('todo:migrate-groups-to-nodes', ['--component' => 11])->assertExitCode(0);

        $this->assertNull(TodoRotatingGroup::find($inScopeGroup->id));
        $this->assertNotNull(TodoRotatingGroup::find($outOfScopeGroup->id), 'other components must be untouched');
        $this->assertSame(0, TodoTaskNode::where('parent_id', $outOfScope->id)->count());
    }

    public function test_orphaned_groups_are_skipped_and_post_checks_fail_the_run(): void
    {
        // A group row whose task node is gone: never crash the run over it, but
        // it survives conversion, so the live-groups post-check must fail loudly.
        TodoRotatingGroup::create([
            'todo_task_node_id' => 999999,
            'group_number' => 1,
            'label' => 'Orphan',
        ]);

        $this->artisan('todo:migrate-groups-to-nodes')->assertExitCode(1);

        $this->assertSame(1, TodoRotatingGroup::count());
    }
}
