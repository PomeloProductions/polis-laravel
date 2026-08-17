<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\Http\User\UserPage;

use App\Models\User\User;
use App\Models\User\UserPage;
use Polis\Tests\Application\ApplicationTestCase;
use Polis\Tests\Traits\MocksApplicationLog;

class UserPageUpdateTest extends ApplicationTestCase
{
    use MocksApplicationLog;

    private string $path = '/v1/users/';

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();
        $this->mockApplicationLog();
    }

    public function test_not_logged_in_user_blocked()
    {
        $user = User::factory()->create();
        $page = UserPage::factory()->create(['user_id' => $user->id]);

        $response = $this->json('PUT', $this->path.$user->id.'/pages/'.$page->id, [
            'name' => 'Updated',
        ]);
        $response->assertStatus(403);
    }

    public function test_different_user_blocked()
    {
        $user = User::factory()->create();
        $page = UserPage::factory()->create(['user_id' => $user->id]);
        $this->actAsUser();

        $response = $this->json('PUT', $this->path.$user->id.'/pages/'.$page->id, [
            'name' => 'Updated',
        ]);
        $response->assertStatus(403);
    }

    public function test_update_successful()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $page = UserPage::factory()->create([
            'user_id' => $user->id,
            'name' => 'Original',
        ]);

        $response = $this->json('PUT', $this->path.$user->id.'/pages/'.$page->id, [
            'name' => 'Updated Name',
            'is_visible' => false,
        ]);

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'name' => 'Updated Name',
            'is_visible' => false,
        ]);
    }

    public function test_update_required_page_cannot_change_protected_fields()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $page = UserPage::factory()->create([
            'user_id' => $user->id,
            'is_required' => true,
            'slug' => 'my-page',
            'route_path' => 'my-page',
            'page_type' => 'dashboard',
        ]);

        $response = $this->json('PUT', $this->path.$user->id.'/pages/'.$page->id, [
            'name' => 'Renamed',
            'slug' => 'changed-slug',
            'route_path' => 'changed-path',
            'page_type' => 'list',
        ]);

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'name' => 'Renamed',
            'slug' => 'my-page',
            'route_path' => 'my-page',
            'page_type' => 'dashboard',
        ]);
    }

    public function test_update_non_required_page_can_change_all_fields()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $page = UserPage::factory()->create([
            'user_id' => $user->id,
            'is_required' => false,
            'slug' => 'my-page',
            'route_path' => 'my-page',
            'page_type' => 'dashboard',
        ]);

        $response = $this->json('PUT', $this->path.$user->id.'/pages/'.$page->id, [
            'name' => 'New Name',
            'slug' => 'new-slug',
            'route_path' => 'new-path',
            'page_type' => 'list',
        ]);

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'name' => 'New Name',
        ]);
    }

    public function test_update_display_order()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $page = UserPage::factory()->create([
            'user_id' => $user->id,
            'display_order' => 0,
        ]);

        $response = $this->json('PUT', $this->path.$user->id.'/pages/'.$page->id, [
            'display_order' => 5,
        ]);

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'display_order' => 5,
        ]);
    }

    public function test_update_config_json()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $page = UserPage::factory()->create([
            'user_id' => $user->id,
            'config_json' => null,
        ]);

        $response = $this->json('PUT', $this->path.$user->id.'/pages/'.$page->id, [
            'config_json' => ['theme' => 'dark'],
        ]);

        $response->assertStatus(200);
    }

    public function test_update_fails_invalid_page_type()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $page = UserPage::factory()->create([
            'user_id' => $user->id,
            'is_required' => false,
        ]);

        $response = $this->json('PUT', $this->path.$user->id.'/pages/'.$page->id, [
            'page_type' => 'nonexistent',
        ]);

        $response->assertStatus(422);
    }

    public function test_update_fails_name_too_long()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $page = UserPage::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->json('PUT', $this->path.$user->id.'/pages/'.$page->id, [
            'name' => str_repeat('a', 101),
        ]);

        $response->assertStatus(422);
    }
}
