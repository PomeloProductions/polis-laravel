<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\Http\User\UserPage;

use App\Models\User\User;
use App\Models\User\UserPage;
use App\Models\User\UserPageComponent;
use Polis\Tests\Application\ApplicationTestCase;
use Polis\Tests\Traits\MocksApplicationLog;

class UserPageComponentCreateTest extends ApplicationTestCase
{
    use MocksApplicationLog;

    private string $path = '/v1/users/';

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();
        $this->mockApplicationLog();
    }

    public function test_create_successful()
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

    public function test_create_invalid_component_type_rejected()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $page = UserPage::factory()->create(['user_id' => $user->id]);

        $response = $this->json('POST', $this->path.$user->id.'/pages/'.$page->id.'/components', [
            'component_type' => 'nonexistent_widget',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'errors' => [
                'component_type' => ['The selected component type is invalid.'],
            ],
        ]);
    }

    public function test_create_different_user_blocked()
    {
        $user = User::factory()->create();
        $page = UserPage::factory()->create(['user_id' => $user->id]);
        $this->actAsUser();

        $response = $this->json('POST', $this->path.$user->id.'/pages/'.$page->id.'/components', [
            'component_type' => 'stats_cards',
        ]);
        $response->assertStatus(403);
    }

    public function test_create_auto_assigns_display_order()
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

    public function test_create_missing_required_fields()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $page = UserPage::factory()->create(['user_id' => $user->id]);

        $response = $this->json('POST', $this->path.$user->id.'/pages/'.$page->id.'/components', []);

        $response->assertStatus(422);
    }

    public function test_create_all_valid_component_types()
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

    public function test_create_fails_component_type_required()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $page = UserPage::factory()->create(['user_id' => $user->id]);

        $response = $this->json('POST', $this->path.$user->id.'/pages/'.$page->id.'/components', []);

        $response->assertStatus(422);
        $response->assertJson([
            'errors' => [
                'component_type' => ['The component type field is required.'],
            ],
        ]);
    }

    public function test_create_fails_component_type_wrong_type()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $page = UserPage::factory()->create(['user_id' => $user->id]);

        $response = $this->json('POST', $this->path.$user->id.'/pages/'.$page->id.'/components', [
            'component_type' => 5,
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'errors' => [
                'component_type' => ['The component type must be a string.'],
            ],
        ]);
    }

    public function test_create_fails_display_order_wrong_type()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $page = UserPage::factory()->create(['user_id' => $user->id]);

        $response = $this->json('POST', $this->path.$user->id.'/pages/'.$page->id.'/components', [
            'component_type' => 'stats_cards',
            'display_order' => 'abc',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'errors' => [
                'display_order' => ['The display order must be an integer.'],
            ],
        ]);
    }

    public function test_create_fails_display_order_negative()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $page = UserPage::factory()->create(['user_id' => $user->id]);

        $response = $this->json('POST', $this->path.$user->id.'/pages/'.$page->id.'/components', [
            'component_type' => 'stats_cards',
            'display_order' => -1,
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'errors' => [
                'display_order' => ['The display order must be at least 0.'],
            ],
        ]);
    }

    public function test_create_fails_config_json_wrong_type()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $page = UserPage::factory()->create(['user_id' => $user->id]);

        $response = $this->json('POST', $this->path.$user->id.'/pages/'.$page->id.'/components', [
            'component_type' => 'stats_cards',
            'config_json' => 'string',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'errors' => [
                'config_json' => ['The config json must be an array.'],
            ],
        ]);
    }
}
