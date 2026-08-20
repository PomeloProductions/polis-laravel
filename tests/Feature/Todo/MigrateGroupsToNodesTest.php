<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\Todo;

use App\Models\User\UserPageComponent;
use Polis\Models\User\TodoRotatingGroup;
use Polis\Models\User\TodoTaskNode;
use Polis\Services\Todo\TodoTaskTreeService;
use Polis\Tests\Application\ApplicationTestCase;
use Polis\Tests\Traits\MocksApplicationLog;

/**
 * todo:migrate-groups-to-nodes — converts TodoRotatingGroup rows into ordinary child nodes:
 * (a) group with children → priority_group node, (b) single-rotating-child groups collapse into
 * the slot, (c) empty groups → bare task slots. Converted rows are soft-deleted (idempotency).
 */
final class MigrateGroupsToNodesTest extends ApplicationTestCase
{
    use MocksApplicationLog;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();
        $this->mockApplicationLog();
    }

    private function makeRotating(): TodoTaskNode
    {
        $component = UserPageComponent::factory()->create(['component_type' => 'todo_task']);

        return TodoTaskNode::create([
            'user_page_component_id' => $component->id,
            'client_id' => 'rot-'.uniqid(),
            'task_type' => TodoTaskNode::TASK_TYPE_ROTATING,
            'label' => 'Rotating',
            'sort_order' => 0,
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
            'mark_done_on_group' => false,
            'cascade_ratio' => 2,
            'sort_order' => 0,
        ], $attrs));
    }

    private function makeGroupChild(TodoRotatingGroup $group, array $attrs = []): TodoTaskNode
    {
        return TodoTaskNode::create(array_merge([
            'user_page_component_id' => $group->taskNode->user_page_component_id,
            'todo_rotating_group_id' => $group->id,
            'parent_id' => null,
            'client_id' => 'item-'.uniqid(),
            'task_type' => TodoTaskNode::TASK_TYPE_LINE_ITEM,
            'label' => 'Item',
            'sort_order' => 0,
        ], $attrs));
    }

    public function test_group_with_children_becomes_priority_group_node(): void
    {
        $rotating = $this->makeRotating();
        $group = $this->makeGroup($rotating, [
            'label' => 'High',
            'count_this_group' => 3,
            'mark_done_on_group' => true,
            'last_date' => '2026-07-10',
        ]);
        $a = $this->makeGroupChild($group, ['label' => 'A', 'sort_order' => 0]);
        $b = $this->makeGroupChild($group, ['label' => 'B', 'sort_order' => 1]);

        $this->artisan('todo:migrate-groups-to-nodes')->assertExitCode(0);

        $slot = TodoTaskNode::where('client_id', 'pg-'.$group->id)->first();
        $this->assertNotNull($slot);
        $this->assertSame(TodoTaskNode::TASK_TYPE_PRIORITY_GROUP, $slot->task_type);
        $this->assertSame($rotating->id, $slot->parent_id);
        $this->assertSame('High', $slot->label);
        $this->assertSame(3, $slot->count_this_group);
        $this->assertSame('2026-07-10', $slot->last_date);
        $this->assertTrue($slot->show_checkmark);
        $this->assertSame('preserve', $slot->on_copy);

        foreach ([$a, $b] as $child) {
            $child->refresh();
            $this->assertSame($slot->id, $child->parent_id);
            $this->assertNull($child->todo_rotating_group_id);
        }

        // Converted group row is soft-deleted (kept as rollback data)
        $this->assertSoftDeleted('todo_rotating_groups', ['id' => $group->id]);
    }

    public function test_single_rotating_child_with_matching_label_collapses(): void
    {
        $rotating = $this->makeRotating();
        $group = $this->makeGroup($rotating, [
            'label' => 'Platform Exploration',
            'count_this_group' => -2, // negative cycle counts are legitimate (cascade resets)
            'last_date' => '2026-07-14',
            'mark_done_on_group' => true,
        ]);
        $nested = $this->makeGroupChild($group, [
            'task_type' => TodoTaskNode::TASK_TYPE_ROTATING,
            'label' => 'Platform Exploration',
        ]);

        $this->artisan('todo:migrate-groups-to-nodes')->assertExitCode(0);

        $nested->refresh();
        // The rotating child IS the slot now — no wrapper node created
        $this->assertSame($rotating->id, $nested->parent_id);
        $this->assertNull($nested->todo_rotating_group_id);
        $this->assertSame(-2, $nested->count_this_group);
        $this->assertSame('2026-07-14', $nested->last_date);
        $this->assertTrue($nested->show_checkmark);
        $this->assertSame(0, TodoTaskNode::where('client_id', 'pg-'.$group->id)->count());
    }

    public function test_single_rotating_child_with_custom_label_does_not_collapse(): void
    {
        $rotating = $this->makeRotating();
        $group = $this->makeGroup($rotating, ['label' => 'Something Custom']);
        $nested = $this->makeGroupChild($group, [
            'task_type' => TodoTaskNode::TASK_TYPE_ROTATING,
            'label' => 'Different Name',
        ]);

        $this->artisan('todo:migrate-groups-to-nodes')->assertExitCode(0);

        // Custom label ≠ child label → keep the wrapper so the label isn't lost
        $slot = TodoTaskNode::where('client_id', 'pg-'.$group->id)->first();
        $this->assertNotNull($slot);
        $this->assertSame('Something Custom', $slot->label);
        $nested->refresh();
        $this->assertSame($slot->id, $nested->parent_id);
    }

    public function test_empty_group_becomes_bare_task_slot(): void
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
        $this->assertTrue($slot->show_checkmark);
        $this->assertSame($rotating->id, $slot->parent_id);
    }

    public function test_rerun_is_idempotent(): void
    {
        $rotating = $this->makeRotating();
        $group = $this->makeGroup($rotating);
        $this->makeGroupChild($group);

        $this->artisan('todo:migrate-groups-to-nodes')->assertExitCode(0);
        $slotsAfterFirst = TodoTaskNode::where('parent_id', $rotating->id)->count();

        $this->artisan('todo:migrate-groups-to-nodes')->assertExitCode(0);
        $this->assertSame($slotsAfterFirst, TodoTaskNode::where('parent_id', $rotating->id)->count());
    }

    public function test_converted_tree_serializes_children_without_groups(): void
    {
        $rotating = $this->makeRotating();
        $group = $this->makeGroup($rotating, ['count_this_group' => 4]);
        $this->makeGroupChild($group, ['label' => 'Inside']);

        $this->artisan('todo:migrate-groups-to-nodes')->assertExitCode(0);

        $tree = app(TodoTaskTreeService::class)->buildTree($rotating->component()->first());
        $root = $tree['root'];

        $this->assertSame(TodoTaskNode::TASK_TYPE_ROTATING, $root['task_type']);
        $this->assertArrayNotHasKey('groups', $root, 'legacy groups key must disappear post-conversion');
        $this->assertCount(1, $root['children']);
        $slot = $root['children'][0];
        $this->assertSame(TodoTaskNode::TASK_TYPE_PRIORITY_GROUP, $slot['task_type']);
        $this->assertSame(4, $slot['count_this_group']);
        $this->assertSame('Inside', $slot['children'][0]['label']);
    }
}
