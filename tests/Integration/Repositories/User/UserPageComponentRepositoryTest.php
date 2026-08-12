<?php

declare(strict_types=1);

namespace Polis\Tests\Integration\Repositories\User;

use App\Models\User\UserPage;
use App\Models\User\UserPageComponent;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Polis\Repositories\User\UserPageComponentRepository;
use Polis\Tests\Application\ApplicationTestCase;
use Polis\Tests\Traits\MocksApplicationLog;

final class UserPageComponentRepositoryTest extends ApplicationTestCase
{
    use MocksApplicationLog;

    protected UserPageComponentRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();

        $this->repository = new UserPageComponentRepository(
            new UserPageComponent,
            $this->getGenericLogMock(),
        );
    }

    public function test_find_all_success(): void
    {
        UserPageComponent::factory()->count(5)->create();
        $items = $this->repository->findAll();
        $this->assertCount(5, $items);
    }

    public function test_find_all_empty(): void
    {
        $items = $this->repository->findAll();
        $this->assertEmpty($items);
    }

    public function test_find_all_filtered_by_page(): void
    {
        $page = UserPage::factory()->create();

        UserPageComponent::factory()->count(3)->create(['user_page_id' => $page->id]);
        UserPageComponent::factory()->count(2)->create();

        $items = $this->repository->findAll([
            ['user_page_id', '=', $page->id],
        ]);
        $this->assertCount(3, $items);
    }

    public function test_find_or_fail_success(): void
    {
        $model = UserPageComponent::factory()->create();

        $foundModel = $this->repository->findOrFail($model->id);
        $this->assertEquals($model->id, $foundModel->id);
    }

    public function test_find_or_fail_fails(): void
    {
        UserPageComponent::factory()->create(['id' => 19]);

        $this->expectException(ModelNotFoundException::class);
        $this->repository->findOrFail(20);
    }

    public function test_create_success(): void
    {
        $page = UserPage::factory()->create();

        /** @var UserPageComponent $component */
        $component = $this->repository->create([
            'user_page_id' => $page->id,
            'component_type' => 'stats_cards',
            'display_order' => 0,
        ]);

        $this->assertEquals(1, UserPageComponent::count());
        $this->assertEquals($page->id, $component->user_page_id);
        $this->assertEquals('stats_cards', $component->component_type);
        $this->assertEquals(0, $component->display_order);
    }

    public function test_create_with_config_json(): void
    {
        $page = UserPage::factory()->create();

        /** @var UserPageComponent $component */
        $component = $this->repository->create([
            'user_page_id' => $page->id,
            'component_type' => 'stats_cards',
            'display_order' => 0,
            'config_json' => ['cards' => [['type' => 'total_count']]],
        ]);

        $this->assertEquals(['cards' => [['type' => 'total_count']]], $component->config_json);
    }

    public function test_create_settings_panel(): void
    {
        $page = UserPage::factory()->create();

        /** @var UserPageComponent $component */
        $component = $this->repository->create([
            'user_page_id' => $page->id,
            'component_type' => 'settings_panel',
            'display_order' => 1,
        ]);

        $this->assertEquals('settings_panel', $component->component_type);
    }

    public function test_create_page_manager(): void
    {
        $page = UserPage::factory()->create();

        /** @var UserPageComponent $component */
        $component = $this->repository->create([
            'user_page_id' => $page->id,
            'component_type' => 'page_manager',
            'display_order' => 2,
        ]);

        $this->assertEquals('page_manager', $component->component_type);
    }

    public function test_update_success(): void
    {
        $model = UserPageComponent::factory()->create([
            'config_json' => ['cards' => []],
        ]);

        $this->repository->update($model, [
            'config_json' => ['cards' => [['type' => 'total_count']]],
        ]);

        $updated = UserPageComponent::find($model->id);
        $this->assertEquals(['cards' => [['type' => 'total_count']]], $updated->config_json);
    }

    public function test_update_display_order(): void
    {
        $model = UserPageComponent::factory()->create([
            'display_order' => 0,
        ]);

        $this->repository->update($model, [
            'display_order' => 3,
        ]);

        $updated = UserPageComponent::find($model->id);
        $this->assertEquals(3, $updated->display_order);
    }

    public function test_delete_success(): void
    {
        $model = UserPageComponent::factory()->create();

        $this->repository->delete($model);

        $this->assertSoftDeleted('user_page_components', ['id' => $model->id]);
    }
}
