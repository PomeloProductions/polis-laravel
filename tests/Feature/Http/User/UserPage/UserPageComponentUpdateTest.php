<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\Http\User\UserPage;

use App\Models\User\User;
use App\Models\User\UserPage;
use App\Models\User\UserPageComponent;
use Polis\Tests\Application\ApplicationTestCase;
use Polis\Tests\Traits\MocksApplicationLog;

class UserPageComponentUpdateTest extends ApplicationTestCase
{
    use MocksApplicationLog;

    private string $path = '/v1/users/';

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();
        $this->mockApplicationLog();
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

    public function test_update_fails_display_order_wrong_type()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $page = UserPage::factory()->create(['user_id' => $user->id]);
        $component = UserPageComponent::factory()->create([
            'user_page_id' => $page->id,
        ]);

        $response = $this->json('PUT', $this->path.$user->id.'/pages/'.$page->id.'/components/'.$component->id, [
            'display_order' => 'abc',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'errors' => [
                'display_order' => ['The display order must be an integer.'],
            ],
        ]);
    }

    public function test_update_fails_display_order_negative()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $page = UserPage::factory()->create(['user_id' => $user->id]);
        $component = UserPageComponent::factory()->create([
            'user_page_id' => $page->id,
        ]);

        $response = $this->json('PUT', $this->path.$user->id.'/pages/'.$page->id.'/components/'.$component->id, [
            'display_order' => -1,
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'errors' => [
                'display_order' => ['The display order must be at least 0.'],
            ],
        ]);
    }

    public function test_update_fails_component_type_wrong_type()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $page = UserPage::factory()->create(['user_id' => $user->id]);
        $component = UserPageComponent::factory()->create([
            'user_page_id' => $page->id,
        ]);

        $response = $this->json('PUT', $this->path.$user->id.'/pages/'.$page->id.'/components/'.$component->id, [
            'component_type' => 5,
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'errors' => [
                'component_type' => ['The component type must be a string.'],
            ],
        ]);
    }

    public function test_update_fails_config_json_wrong_type()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $page = UserPage::factory()->create(['user_id' => $user->id]);
        $component = UserPageComponent::factory()->create([
            'user_page_id' => $page->id,
        ]);

        $response = $this->json('PUT', $this->path.$user->id.'/pages/'.$page->id.'/components/'.$component->id, [
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
