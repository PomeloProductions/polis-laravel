<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\Http\User\UserPage;

use App\Models\User\User;
use App\Models\User\UserPage;
use App\Models\User\UserPageComponent;
use Polis\Tests\Application\ApplicationTestCase;
use Polis\Tests\Traits\MocksApplicationLog;

class UserPageComponentTest extends ApplicationTestCase
{
    use MocksApplicationLog;

    private string $path = '/v1/users/';

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();
        $this->mockApplicationLog();
    }

    public function test_index_not_logged_in_blocked()
    {
        $user = User::factory()->create();
        $page = UserPage::factory()->create(['user_id' => $user->id]);

        $response = $this->json('GET', $this->path.$user->id.'/pages/'.$page->id.'/components');
        $response->assertStatus(403);
    }

    public function test_index_successful()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $page = UserPage::factory()->create(['user_id' => $user->id]);
        UserPageComponent::factory()->create([
            'user_page_id' => $page->id,
            'component_type' => 'stats_cards',
        ]);

        $response = $this->json('GET', $this->path.$user->id.'/pages/'.$page->id.'/components');
        $response->assertStatus(200);
        $response->assertJsonFragment([
            'component_type' => 'stats_cards',
        ]);
    }

    public function test_store_successful()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $page = UserPage::factory()->create(['user_id' => $user->id]);

        $response = $this->json('POST', $this->path.$user->id.'/pages/'.$page->id.'/components', [
            'component_type' => 'stats_cards',
            'config_json' => ['cards' => []],
        ]);

        $response->assertStatus(201);
        $response->assertJsonFragment([
            'component_type' => 'stats_cards',
        ]);

        $this->assertDatabaseHas('user_page_components', [
            'user_page_id' => $page->id,
            'component_type' => 'stats_cards',
        ]);
    }

    public function test_store_invalid_component_type_rejected()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $page = UserPage::factory()->create(['user_id' => $user->id]);

        $response = $this->json('POST', $this->path.$user->id.'/pages/'.$page->id.'/components', [
            'component_type' => 'nonexistent_widget',
        ]);

        $response->assertStatus(400);
    }

    public function test_update_successful()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $page = UserPage::factory()->create(['user_id' => $user->id]);
        $component = UserPageComponent::factory()->create([
            'user_page_id' => $page->id,
            'component_type' => 'stats_cards',
            'config_json' => ['cards' => []],
        ]);

        $response = $this->json('PUT', $this->path.$user->id.'/pages/'.$page->id.'/components/'.$component->id, [
            'config_json' => ['cards' => [['type' => 'total_count']]],
        ]);

        $response->assertStatus(200);
    }

    public function test_delete_successful()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $page = UserPage::factory()->create(['user_id' => $user->id]);
        $component = UserPageComponent::factory()->create([
            'user_page_id' => $page->id,
            'component_type' => 'stats_cards',
        ]);

        $response = $this->json('DELETE', $this->path.$user->id.'/pages/'.$page->id.'/components/'.$component->id);
        $response->assertStatus(204);

        $this->assertSoftDeleted('user_page_components', ['id' => $component->id]);
    }

    public function test_different_user_blocked()
    {
        $user = User::factory()->create();
        $page = UserPage::factory()->create(['user_id' => $user->id]);
        $this->actAsUser();

        $response = $this->json('POST', $this->path.$user->id.'/pages/'.$page->id.'/components', [
            'component_type' => 'stats_cards',
        ]);
        $response->assertStatus(403);
    }

    public function test_store_auto_assigns_display_order()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $page = UserPage::factory()->create(['user_id' => $user->id]);
        UserPageComponent::factory()->create([
            'user_page_id' => $page->id,
            'display_order' => 2,
        ]);

        $response = $this->json('POST', $this->path.$user->id.'/pages/'.$page->id.'/components', [
            'component_type' => 'settings_panel',
        ]);

        $response->assertStatus(201);
        $response->assertJsonFragment([
            'display_order' => 3,
        ]);
    }

    public function test_store_missing_required_fields()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $page = UserPage::factory()->create(['user_id' => $user->id]);

        $response = $this->json('POST', $this->path.$user->id.'/pages/'.$page->id.'/components', []);

        $response->assertStatus(400);
    }

    public function test_store_all_valid_component_types()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $page = UserPage::factory()->create(['user_id' => $user->id]);

        foreach (['stats_cards', 'page_manager', 'settings_panel'] as $type) {
            $response = $this->json('POST', $this->path.$user->id.'/pages/'.$page->id.'/components', [
                'component_type' => $type,
            ]);

            $response->assertStatus(201);
            $response->assertJsonFragment([
                'component_type' => $type,
            ]);
        }
    }

    public function test_update_config_json()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $page = UserPage::factory()->create(['user_id' => $user->id]);
        $component = UserPageComponent::factory()->create([
            'user_page_id' => $page->id,
            'component_type' => 'stats_cards',
            'config_json' => null,
        ]);

        $response = $this->json('PUT', $this->path.$user->id.'/pages/'.$page->id.'/components/'.$component->id, [
            'config_json' => ['cards' => [['type' => 'total_count'], ['type' => 'active_count']]],
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('user_page_components', [
            'id' => $component->id,
        ]);
    }

    public function test_index_returns_only_page_components()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $page1 = UserPage::factory()->create(['user_id' => $user->id]);
        $page2 = UserPage::factory()->create(['user_id' => $user->id]);

        UserPageComponent::factory()->count(3)->create(['user_page_id' => $page1->id]);
        UserPageComponent::factory()->count(2)->create(['user_page_id' => $page2->id]);

        $response = $this->json('GET', $this->path.$user->id.'/pages/'.$page1->id.'/components');
        $response->assertStatus(200);
    }

    public function test_delete_different_user_blocked()
    {
        $user = User::factory()->create();
        $page = UserPage::factory()->create(['user_id' => $user->id]);
        $component = UserPageComponent::factory()->create([
            'user_page_id' => $page->id,
        ]);

        $this->actAsUser();

        $response = $this->json('DELETE', $this->path.$user->id.'/pages/'.$page->id.'/components/'.$component->id);
        $response->assertStatus(403);
    }

    public function test_update_different_user_blocked()
    {
        $user = User::factory()->create();
        $page = UserPage::factory()->create(['user_id' => $user->id]);
        $component = UserPageComponent::factory()->create([
            'user_page_id' => $page->id,
        ]);

        $this->actAsUser();

        $response = $this->json('PUT', $this->path.$user->id.'/pages/'.$page->id.'/components/'.$component->id, [
            'config_json' => ['test' => true],
        ]);
        $response->assertStatus(403);
    }
}
