<?php

declare(strict_types=1);

namespace Polis\Tests\Integration\Repositories\User;

use App\Models\User\User;
use App\Models\User\UserPage;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Polis\Repositories\User\UserPageRepository;
use Polis\Tests\DatabaseSetupTrait;
use Polis\Tests\TestCase;
use Polis\Tests\Traits\MocksApplicationLog;

final class UserPageRepositoryTest extends TestCase
{
    use DatabaseSetupTrait, MocksApplicationLog;

    protected UserPageRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();

        $this->repository = new UserPageRepository(
            new UserPage,
            $this->getGenericLogMock(),
        );
    }

    public function test_find_all_success(): void
    {
        UserPage::factory()->count(5)->create();
        $items = $this->repository->findAll();
        $this->assertCount(5, $items);
    }

    public function test_find_all_empty(): void
    {
        $items = $this->repository->findAll();
        $this->assertEmpty($items);
    }

    public function test_find_all_filtered_by_user(): void
    {
        $user = User::factory()->create();

        UserPage::factory()->count(3)->create(['user_id' => $user->id]);
        UserPage::factory()->count(2)->create();

        $items = $this->repository->findAll([
            ['user_id', '=', $user->id],
        ]);
        $this->assertCount(3, $items);
    }

    public function test_find_or_fail_success(): void
    {
        $model = UserPage::factory()->create();

        $foundModel = $this->repository->findOrFail($model->id);
        $this->assertEquals($model->id, $foundModel->id);
    }

    public function test_find_or_fail_fails(): void
    {
        UserPage::factory()->create(['id' => 19]);

        $this->expectException(ModelNotFoundException::class);
        $this->repository->findOrFail(20);
    }

    public function test_create_success(): void
    {
        $user = User::factory()->create();

        /** @var UserPage $page */
        $page = $this->repository->create([
            'user_id' => $user->id,
            'slug' => 'test-page',
            'name' => 'Test Page',
            'icon' => 'IconList',
            'route_path' => 'test-page',
            'page_type' => 'dashboard',
            'display_order' => 0,
            'is_visible' => true,
            'is_required' => false,
            'is_nav_item' => true,
        ]);

        $this->assertEquals(1, UserPage::count());
        $this->assertEquals($user->id, $page->user_id);
        $this->assertEquals('test-page', $page->slug);
        $this->assertEquals('Test Page', $page->name);
        $this->assertEquals('dashboard', $page->page_type);
        $this->assertTrue($page->is_visible);
        $this->assertFalse($page->is_required);
    }

    public function test_create_with_parent_page(): void
    {
        $user = User::factory()->create();
        $parent = UserPage::factory()->create([
            'user_id' => $user->id,
            'page_type' => 'dashboard',
        ]);

        /** @var UserPage $child */
        $child = $this->repository->create([
            'user_id' => $user->id,
            'slug' => 'child-page',
            'name' => 'Child Page',
            'icon' => 'IconList',
            'route_path' => 'child-page',
            'page_type' => 'list',
            'display_order' => 0,
            'is_visible' => true,
            'is_required' => false,
            'is_nav_item' => true,
            'parent_page_id' => $parent->id,
        ]);

        $this->assertEquals($parent->id, $child->parent_page_id);
    }

    public function test_create_with_config_json(): void
    {
        $user = User::factory()->create();

        /** @var UserPage $page */
        $page = $this->repository->create([
            'user_id' => $user->id,
            'slug' => 'configured-page',
            'name' => 'Configured Page',
            'icon' => 'IconList',
            'route_path' => 'configured-page',
            'page_type' => 'dashboard',
            'display_order' => 0,
            'is_visible' => true,
            'is_required' => false,
            'is_nav_item' => true,
            'config_json' => ['theme' => 'dark'],
        ]);

        $this->assertEquals(['theme' => 'dark'], $page->config_json);
    }

    public function test_update_success(): void
    {
        $model = UserPage::factory()->create([
            'name' => 'Original Name',
        ]);

        $this->repository->update($model, [
            'name' => 'Updated Name',
        ]);

        $updated = UserPage::find($model->id);
        $this->assertEquals('Updated Name', $updated->name);
    }

    public function test_update_visibility(): void
    {
        $model = UserPage::factory()->create([
            'is_visible' => true,
        ]);

        $this->repository->update($model, [
            'is_visible' => false,
        ]);

        $updated = UserPage::find($model->id);
        $this->assertFalse($updated->is_visible);
    }

    public function test_delete_success(): void
    {
        $model = UserPage::factory()->create();

        $this->repository->delete($model);

        $this->assertSoftDeleted('user_pages', ['id' => $model->id]);
    }
}
