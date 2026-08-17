<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\Todo;

use App\Models\Role;
use Polis\Models\User\TodoTaskNode;

/**
 * HTTP feature coverage for TodoControllerAbstract@patchNode — the relational
 * task-node editing endpoint. This drives TodoTaskTreeService::buildTree and
 * TodoNodeTreeCodec (the serialized node tree returned to the client) as well
 * as the sub-item / children sync helpers.
 */
final class TodoNodeTest extends TodoFeatureTestCase
{
    public function test_patch_node_requires_authentication(): void
    {
        $node = $this->createDayPageWithNode($this->otherUser()->id, 'n-unauth');
        $response = $this->json('PATCH', $this->base($node->component->page->user_id).'/nodes/'.$node->client_id, [
            'component_id' => $node->user_page_component_id,
            'label' => 'x',
        ]);
        $response->assertStatus(403);
    }

    public function test_patch_node_denies_cross_user_access(): void
    {
        $this->actAs(Role::APP_USER);
        $other = $this->otherUser();
        $node = $this->createDayPageWithNode($other->id, 'n-cross');

        $response = $this->json('PATCH', $this->base($other->id).'/nodes/'.$node->client_id, [
            'component_id' => $node->user_page_component_id,
            'label' => 'x',
        ]);

        $response->assertStatus(403);
    }

    public function test_patch_unknown_node_is_not_found(): void
    {
        // patchNode resolves the node with firstOrFail scoped to the user's
        // pages; an unknown client_id yields a 404.
        $this->actAs(Role::APP_USER);
        $this->createDayPageWithNode($this->actingAs->id, 'exists');

        $response = $this->json('PATCH', $this->base($this->actingAs->id).'/nodes/no-such-client-id', [
            'label' => 'x',
        ]);

        $response->assertStatus(404);
    }

    public function test_patch_node_updates_scalar_fields_and_returns_tree(): void
    {
        $this->actAs(Role::APP_USER);
        $node = $this->createDayPageWithNode($this->actingAs->id, 'n-1');

        $response = $this->json('PATCH', $this->base($this->actingAs->id).'/nodes/'.$node->client_id, [
            'component_id' => $node->user_page_component_id,
            'label' => 'Renamed task',
            'tally' => 3,
        ]);

        $response->assertStatus(200);
        // The controller returns the rebuilt node tree (codec output).
        $response->assertJsonPath('root.label', 'Renamed task');
        $this->assertSame(
            'Renamed task',
            TodoTaskNode::where('id', $node->id)->value('label'),
        );
    }

    public function test_patch_node_syncs_sub_items(): void
    {
        $this->actAs(Role::APP_USER);
        $node = $this->createDayPageWithNode($this->actingAs->id, 'n-sub');

        $response = $this->json('PATCH', $this->base($this->actingAs->id).'/nodes/'.$node->client_id, [
            'component_id' => $node->user_page_component_id,
            'sub_items' => [
                ['text' => 'First step', 'completed' => false],
                ['text' => 'Second step', 'completed' => true],
            ],
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('todo_sub_items', [
            'todo_task_node_id' => $node->id,
            'text' => 'First step',
        ]);
    }

    public function test_patch_category_node_syncs_children(): void
    {
        $this->actAs(Role::APP_USER);
        $layout = $this->createDayPageWithTwoComponents($this->actingAs->id);
        $category = TodoTaskNode::create([
            'user_page_component_id' => $layout['componentA']->id,
            'client_id' => 'cat-1',
            'task_type' => 'category',
            'label' => 'Category',
            'tally' => 0,
        ]);

        $response = $this->json('PATCH', $this->base($this->actingAs->id).'/nodes/'.$category->client_id, [
            'component_id' => $layout['componentA']->id,
            'children' => [
                ['id' => 'child-1', 'label' => 'Child one', 'task_type' => 'line_item'],
            ],
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('todo_task_nodes', [
            'client_id' => 'child-1',
            'parent_id' => $category->id,
            'label' => 'Child one',
        ]);
    }

    public function test_patch_node_move_relocates_across_components(): void
    {
        // Drives TodoTaskTreeService::moveNode + NodeTreeService::moveNode: a
        // leaf in component B is moved under a category in component A.
        $this->actAs(Role::APP_USER);
        $layout = $this->createDayPageWithTwoComponents($this->actingAs->id);
        $category = TodoTaskNode::create([
            'user_page_component_id' => $layout['componentA']->id,
            'client_id' => 'cat-2',
            'task_type' => 'category',
            'label' => 'Target category',
            'tally' => 0,
        ]);
        $leaf = TodoTaskNode::create([
            'user_page_component_id' => $layout['componentB']->id,
            'client_id' => 'leaf-2',
            'task_type' => 'line_item',
            'label' => 'Movable leaf',
            'tally' => 0,
        ]);

        $response = $this->json('PATCH', $this->base($this->actingAs->id).'/nodes/'.$leaf->client_id, [
            'component_id' => $layout['componentB']->id,
            '_move' => [
                'target_component_id' => $layout['componentA']->id,
                'target_parent_client_id' => $category->client_id,
                'target_sort_order' => 0,
            ],
        ]);

        $response->assertStatus(200);
        $moved = TodoTaskNode::find($leaf->id);
        $this->assertSame($layout['componentA']->id, $moved->user_page_component_id);
        $this->assertSame($category->id, $moved->parent_id);
    }

    public function test_patch_rotating_node_syncs_groups(): void
    {
        $this->actAs(Role::APP_USER);
        $node = $this->createDayPageWithNode($this->actingAs->id, 'rot-1');

        $response = $this->json('PATCH', $this->base($this->actingAs->id).'/nodes/'.$node->client_id, [
            'component_id' => $node->user_page_component_id,
            'task_type' => 'rotating',
            'groups' => [
                ['group_number' => 1, 'label' => 'Priority 1', 'count_this_group' => 1, 'children' => []],
            ],
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('todo_rotating_groups', [
            'todo_task_node_id' => $node->id,
            'group_number' => 1,
            'label' => 'Priority 1',
        ]);
    }
}
