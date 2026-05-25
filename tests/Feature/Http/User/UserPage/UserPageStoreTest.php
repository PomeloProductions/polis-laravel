<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\Http\User\UserPage;

use App\Models\User\User;
use App\Models\User\UserPage;
use Polis\Tests\DatabaseSetupTrait;
use Polis\Tests\TestCase;
use Polis\Tests\Traits\MocksApplicationLog;

class UserPageStoreTest extends TestCase
{
    use DatabaseSetupTrait, MocksApplicationLog;

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

        $response = $this->json('POST', $this->path.$user->id.'/pages', [
            'name' => 'Test Page',
            'route_path' => 'test-page',
            'page_type' => 'list',
        ]);
        $response->assertStatus(403);
    }

    public function test_different_user_blocked()
    {
        $user = User::factory()->create();
        $this->actAsUser();

        $response = $this->json('POST', $this->path.$user->id.'/pages', [
            'name' => 'Test Page',
            'route_path' => 'test-page',
            'page_type' => 'list',
        ]);
        $response->assertStatus(403);
    }

    public function test_store_successful()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->json('POST', $this->path.$user->id.'/pages', [
            'name' => 'Custom Page',
            'route_path' => 'custom-page',
            'page_type' => 'list',
        ]);

        $response->assertStatus(201);
        $response->assertJsonFragment([
            'name' => 'Custom Page',
            'route_path' => 'custom-page',
            'page_type' => 'list',
            'is_required' => false,
        ]);

        $this->assertDatabaseHas('user_pages', [
            'user_id' => $user->id,
            'name' => 'Custom Page',
        ]);
    }

    public function test_store_auto_generates_slug()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->json('POST', $this->path.$user->id.'/pages', [
            'name' => 'My Custom Page',
            'route_path' => 'my-custom-page',
            'page_type' => 'list',
        ]);

        $response->assertStatus(201);
        $response->assertJsonFragment([
            'slug' => 'my-custom-page',
        ]);
    }

    public function test_store_validation_fails()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->json('POST', $this->path.$user->id.'/pages', [
            'name' => 'Test',
        ]);

        $response->assertStatus(400);
    }

    public function test_store_invalid_page_type()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->json('POST', $this->path.$user->id.'/pages', [
            'name' => 'Test',
            'route_path' => 'test',
            'page_type' => 'invalid',
        ]);

        $response->assertStatus(400);
    }

    public function test_store_auto_assigns_display_order()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        UserPage::factory()->create([
            'user_id' => $user->id,
            'display_order' => 2,
        ]);

        $response = $this->json('POST', $this->path.$user->id.'/pages', [
            'name' => 'New Page',
            'route_path' => 'new-page',
            'page_type' => 'list',
        ]);

        $response->assertStatus(201);
        $response->assertJsonFragment([
            'display_order' => 3,
        ]);
    }

    public function test_store_slug_collision_appends_counter()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        UserPage::factory()->create([
            'user_id' => $user->id,
            'slug' => 'my-page',
        ]);

        $response = $this->json('POST', $this->path.$user->id.'/pages', [
            'name' => 'My Page',
            'route_path' => 'my-page',
            'page_type' => 'list',
        ]);

        $response->assertStatus(201);
        $response->assertJsonFragment([
            'slug' => 'my-page-1',
        ]);
    }

    public function test_store_always_sets_is_required_false()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->json('POST', $this->path.$user->id.'/pages', [
            'name' => 'My Page',
            'route_path' => 'my-page',
            'page_type' => 'dashboard',
        ]);

        $response->assertStatus(201);
        $response->assertJsonFragment([
            'is_required' => false,
        ]);
    }

    public function test_store_defaults_icon()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->json('POST', $this->path.$user->id.'/pages', [
            'name' => 'Test Page',
            'route_path' => 'test-page',
            'page_type' => 'list',
        ]);

        $response->assertStatus(201);
        $response->assertJsonFragment([
            'icon' => 'IconList',
        ]);
    }

    public function test_store_fails_strings_too_long()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->json('POST', $this->path.$user->id.'/pages', [
            'name' => str_repeat('a', 101),
            'route_path' => str_repeat('a', 101),
            'page_type' => 'list',
        ]);

        $response->assertStatus(400);
    }

    public function test_store_with_all_page_types()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        foreach (['dashboard', 'list', 'detail'] as $type) {
            $response = $this->json('POST', $this->path.$user->id.'/pages', [
                'name' => "Page $type",
                'route_path' => "page-$type",
                'page_type' => $type,
            ]);

            $response->assertStatus(201);
            $response->assertJsonFragment([
                'page_type' => $type,
            ]);
        }
    }
}
