<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\Http\User\Todo;

use App\Models\User\TodoBalance;
use App\Models\User\TodoBalanceLog;
use App\Models\User\TodoRotatingGroup;
use App\Models\User\TodoRotatingItem;
use App\Models\User\TodoSubItem;
use App\Models\User\TodoTaskNode;
use App\Models\User\User;
use App\Models\User\UserPage;
use Polis\Models\User\UserPageComponent;
use Polis\Tests\Application\ApplicationTestCase;
use Polis\Tests\Traits\MocksApplicationLog;

class TodoPatchNodeTest extends ApplicationTestCase
{
    use MocksApplicationLog;

    private string $path = '/v1/users/';

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();
        $this->mockApplicationLog();
    }

    private function createNodeSetup(User $user): array
    {
        $page = UserPage::factory()->create([
            'user_id' => $user->id,
            'page_type' => 'todo',
            'config_json' => ['todo_level' => 'day', 'todo_date' => '2026-04-15'],
        ]);

        $component = UserPageComponent::create([
            'user_page_id' => $page->id,
            'component_type' => 'todo_task',
            'display_order' => 0,
            'config_json' => [],
        ]);

        $node = TodoTaskNode::create([
            'user_page_component_id' => $component->id,
            'client_id' => 'test-node-1',
            'task_type' => 'rotating',
            'label' => 'Test Rotating',
            'tally' => 10,
            'tally_step' => 1,
            'tracking_mode' => 'units',
        ]);

        return [$page, $component, $node];
    }

    public function test_not_logged_in_blocked(): void
    {
        $user = User::factory()->create();
        [$page, $component, $node] = $this->createNodeSetup($user);

        $response = $this->json('PATCH', $this->path.$user->id.'/todos/nodes/'.$node->client_id, [
            'component_id' => $component->id,
            'tally' => 9,
        ]);
        $response->assertStatus(403);
    }

    public function test_different_user_blocked(): void
    {
        $user = User::factory()->create();
        [$page, $component, $node] = $this->createNodeSetup($user);
        $this->actAsUser();

        $response = $this->json('PATCH', $this->path.$user->id.'/todos/nodes/'.$node->client_id, [
            'component_id' => $component->id,
            'tally' => 9,
        ]);
        $response->assertStatus(403);
    }

    public function test_patch_scalar_fields(): void
    {
        $user = User::factory()->create();
        [$page, $component, $node] = $this->createNodeSetup($user);
        $this->actingAs($user);

        $response = $this->json('PATCH', $this->path.$user->id.'/todos/nodes/'.$node->client_id, [
            'component_id' => $component->id,
            'tally' => 9,
            'label' => 'Updated Label',
        ]);

        $response->assertStatus(200);
        $node->refresh();
        $this->assertEquals(9, (float) $node->tally);
        $this->assertEquals('Updated Label', $node->label);
    }

    public function test_patch_groups(): void
    {
        $user = User::factory()->create();
        [$page, $component, $node] = $this->createNodeSetup($user);
        $this->actingAs($user);

        // Create groups
        $group = TodoRotatingGroup::create([
            'todo_task_node_id' => $node->id,
            'group_number' => 1,
            'label' => 'Group 1',
            'count_this_group' => 3,
        ]);
        $item = TodoRotatingItem::create([
            'todo_rotating_group_id' => $group->id,
            'client_id' => 'ri-1',
            'text' => 'Item 1',
            'count' => 2,
        ]);

        $response = $this->json('PATCH', $this->path.$user->id.'/todos/nodes/'.$node->client_id, [
            'component_id' => $component->id,
            'groups' => [
                [
                    'group_number' => 1,
                    'count_this_group' => 5,
                    'items' => [
                        ['id' => 'ri-1', 'text' => 'Item 1', 'count' => 4, 'last_date' => '2026-04-15T10:00:00Z'],
                    ],
                ],
            ],
        ]);

        $response->assertStatus(200);
        $group->refresh();
        $item->refresh();
        $this->assertEquals(5, $group->count_this_group);
        $this->assertEquals(4, $item->count);
        $this->assertEquals('2026-04-15T10:00:00Z', $item->last_date);
    }

    public function test_patch_unit_mode_logs_balance(): void
    {
        $user = User::factory()->create();
        [$page, $component, $node] = $this->createNodeSetup($user);
        $this->actingAs($user);

        $balance = TodoBalance::create([
            'user_id' => $user->id,
            'item_key' => 'Test Rotating',
            'tracking_mode' => 'units',
            'balance' => 10,
            'tally_step' => 1,
        ]);
        $node->update(['todo_balance_id' => $balance->id]);

        $response = $this->json('PATCH', $this->path.$user->id.'/todos/nodes/'.$node->client_id, [
            'component_id' => $component->id,
            'tally' => 9,
        ]);

        $response->assertStatus(200);
        $balance->refresh();
        $this->assertEquals(9, (float) $balance->balance);

        $log = TodoBalanceLog::where('todo_balance_id', $balance->id)->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertEquals('mark_done', $log->reason);
        $this->assertEquals(-1, (float) $log->delta);
    }

    public function test_patch_hours_mode_skips_balance_without_manual_flag(): void
    {
        $user = User::factory()->create();
        [$page, $component, $node] = $this->createNodeSetup($user);
        $this->actingAs($user);

        $node->update(['tracking_mode' => 'hours', 'tally' => -5.0]);
        $balance = TodoBalance::create([
            'user_id' => $user->id,
            'item_key' => 'Test Rotating',
            'tracking_mode' => 'hours',
            'balance' => -5.0,
            'tally_step' => 0.5,
        ]);
        $node->update(['todo_balance_id' => $balance->id]);

        $response = $this->json('PATCH', $this->path.$user->id.'/todos/nodes/'.$node->client_id, [
            'component_id' => $component->id,
            'tally' => -5.5,
        ]);

        $response->assertStatus(200);
        $balance->refresh();
        // Balance should NOT change — no _manual_balance_edit flag
        $this->assertEquals(-5.0, (float) $balance->balance);
        // Tally should also not change for hours-mode without manual flag
        $node->refresh();
        $this->assertEquals(-5.0, (float) $node->tally);
    }

    public function test_patch_hours_mode_updates_balance_with_manual_flag(): void
    {
        $user = User::factory()->create();
        [$page, $component, $node] = $this->createNodeSetup($user);
        $this->actingAs($user);

        $node->update(['tracking_mode' => 'hours', 'tally' => -5.0]);
        $balance = TodoBalance::create([
            'user_id' => $user->id,
            'item_key' => 'Test Rotating',
            'tracking_mode' => 'hours',
            'balance' => -5.0,
            'tally_step' => 0.5,
        ]);
        $node->update(['todo_balance_id' => $balance->id]);

        $response = $this->json('PATCH', $this->path.$user->id.'/todos/nodes/'.$node->client_id, [
            'component_id' => $component->id,
            'tally' => -6.0,
            '_manual_balance_edit' => true,
        ]);

        $response->assertStatus(200);
        $balance->refresh();
        $this->assertEquals(-6.0, (float) $balance->balance);
        $node->refresh();
        $this->assertEquals(-6.0, (float) $node->tally);
    }

    public function test_patch_sub_items(): void
    {
        $user = User::factory()->create();
        [$page, $component, $node] = $this->createNodeSetup($user);
        $node->update(['task_type' => 'line_item']);
        $this->actingAs($user);

        $sub = TodoSubItem::create([
            'todo_task_node_id' => $node->id,
            'client_id' => 'si-1',
            'text' => 'Sub 1',
            'completed' => false,
        ]);

        $response = $this->json('PATCH', $this->path.$user->id.'/todos/nodes/'.$node->client_id, [
            'component_id' => $component->id,
            'sub_items' => [
                ['id' => 'si-1', 'text' => 'Sub 1 Updated', 'completed' => true],
            ],
        ]);

        $response->assertStatus(200);
        $sub->refresh();
        $this->assertEquals('Sub 1 Updated', $sub->text);
        $this->assertTrue($sub->completed);
    }

    public function test_patch_move(): void
    {
        $user = User::factory()->create();
        [$page, $component, $node] = $this->createNodeSetup($user);
        $this->actingAs($user);

        // Create a target category component
        $targetComp = UserPageComponent::create([
            'user_page_id' => $page->id,
            'component_type' => 'todo_task',
            'display_order' => 1,
            'config_json' => [],
        ]);
        $targetRoot = TodoTaskNode::create([
            'user_page_component_id' => $targetComp->id,
            'client_id' => 'target-root',
            'task_type' => 'category',
            'label' => 'Target Category',
        ]);

        $response = $this->json('PATCH', $this->path.$user->id.'/todos/nodes/'.$node->client_id, [
            'component_id' => $component->id,
            '_move' => [
                'target_component_id' => $targetComp->id,
                'target_parent_client_id' => 'target-root',
                'target_sort_order' => 0,
            ],
        ]);

        $response->assertStatus(200);
        $node->refresh();
        $this->assertEquals($targetComp->id, $node->user_page_component_id);
        $this->assertEquals($targetRoot->id, $node->parent_id);
    }

    public function test_node_not_found(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->json('PATCH', $this->path.$user->id.'/todos/nodes/nonexistent', [
            'tally' => 5,
        ]);
        $response->assertStatus(404);
    }
}
