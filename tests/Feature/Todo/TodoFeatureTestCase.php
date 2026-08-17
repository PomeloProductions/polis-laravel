<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\Todo;

use App\Models\User\User;
use App\Models\User\UserPage;
use App\Models\User\UserPageComponent;
use Polis\Models\User\TodoTaskNode;
use Polis\Tests\Application\ApplicationTestCase;
use Polis\Tests\Traits\MocksApplicationLog;

/**
 * Shared base for the Todo HTTP feature tests. Provides small builders for the
 * root todo page + a todo_task component/node so the deep generation and
 * tree-building paths (PeriodPageGenerationService, TodoPeriodLadder,
 * TodoTaskTreeService, TodoNodeTreeCodec) actually run under test.
 */
abstract class TodoFeatureTestCase extends ApplicationTestCase
{
    use MocksApplicationLog;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockApplicationLog();
    }

    protected function base(int $userId): string
    {
        return '/v1/users/'.$userId.'/todos';
    }

    /**
     * Create the root `page_type = todo` page the period generator hangs the
     * year/month/week/day ladder off of.
     */
    protected function createRootTodoPage(int $userId): UserPage
    {
        return UserPage::create([
            'user_id' => $userId,
            'slug' => 'todo-root-'.$userId,
            'name' => 'Todo',
            'icon' => 'IconList',
            'route_path' => 'todo-'.$userId,
            'page_type' => 'todo',
            'display_order' => 0,
            'is_visible' => true,
            'is_required' => false,
            'is_nav_item' => true,
            'parent_page_id' => null,
            'config_json' => ['todo_level' => 'root'],
        ]);
    }

    /**
     * Create a day page carrying a single todo_task component + one line-item
     * node. Returns the node so tests can PATCH it by client_id.
     */
    protected function createDayPageWithNode(int $userId, string $clientId = 'node-1'): TodoTaskNode
    {
        $page = UserPage::create([
            'user_id' => $userId,
            'slug' => 'day-'.$userId.'-'.$clientId,
            'name' => 'Day',
            'icon' => 'IconCalendar',
            'route_path' => 'day-'.$userId.'-'.$clientId,
            'page_type' => 'todo',
            'display_order' => 0,
            'is_visible' => true,
            'is_required' => false,
            'is_nav_item' => true,
            'config_json' => ['todo_level' => 'day', 'todo_date' => now()->toDateString()],
        ]);

        $component = UserPageComponent::create([
            'user_page_id' => $page->id,
            'component_type' => 'todo_task',
            'display_order' => 0,
            'config_json' => null,
        ]);

        return TodoTaskNode::create([
            'user_page_component_id' => $component->id,
            'client_id' => $clientId,
            'task_type' => 'line_item',
            'label' => 'Task A',
            'tally' => 0,
        ]);
    }

    protected function otherUser(): User
    {
        return User::factory()->create();
    }

    /**
     * Create a day page with two todo_task components, returning both so tests
     * can drive the cross-component move path of TodoTaskTreeService.
     *
     * @return array{page: UserPage, componentA: UserPageComponent, componentB: UserPageComponent}
     */
    protected function createDayPageWithTwoComponents(int $userId): array
    {
        $page = UserPage::create([
            'user_id' => $userId,
            'slug' => 'day2-'.$userId,
            'name' => 'Day',
            'icon' => 'IconCalendar',
            'route_path' => 'day2-'.$userId,
            'page_type' => 'todo',
            'display_order' => 0,
            'is_visible' => true,
            'is_required' => false,
            'is_nav_item' => true,
            'config_json' => ['todo_level' => 'day', 'todo_date' => now()->toDateString()],
        ]);

        $componentA = UserPageComponent::create([
            'user_page_id' => $page->id,
            'component_type' => 'todo_task',
            'display_order' => 0,
            'config_json' => null,
        ]);
        $componentB = UserPageComponent::create([
            'user_page_id' => $page->id,
            'component_type' => 'todo_task',
            'display_order' => 1,
            'config_json' => null,
        ]);

        return ['page' => $page, 'componentA' => $componentA, 'componentB' => $componentB];
    }
}
