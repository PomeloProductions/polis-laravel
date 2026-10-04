<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\Http\User\UserPage;

use App\Models\User\User;
use App\Models\User\UserPage;
use Polis\Tests\Application\ApplicationTestCase;
use Polis\Tests\Traits\MocksApplicationLog;

class UserPageCreateTest extends ApplicationTestCase
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

    public function test_create_successful()
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

    public function test_create_auto_generates_slug()
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

    public function test_create_validation_fails()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->json('POST', $this->path.$user->id.'/pages', [
            'name' => 'Test',
        ]);

        $response->assertStatus(422);
    }

    public function test_create_invalid_page_type()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->json('POST', $this->path.$user->id.'/pages', [
            'name' => 'Test',
            'route_path' => 'test',
            'page_type' => 'invalid',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'errors' => [
                'page_type' => ['The selected page type is invalid.'],
            ],
        ]);
    }

    public function test_create_auto_assigns_display_order()
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

    public function test_create_slug_collision_appends_counter()
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

    public function test_create_always_sets_is_required_false()
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

    public function test_create_defaults_icon()
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

    public function test_create_fails_strings_too_long()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->json('POST', $this->path.$user->id.'/pages', [
            'name' => str_repeat('a', 101),
            'route_path' => str_repeat('a', 101),
            'page_type' => 'list',
        ]);

        $response->assertStatus(422);
    }

    public function test_create_with_all_page_types()
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

    public function test_create_fails_missing_required_fields()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->json('POST', $this->path.$user->id.'/pages', []);

        $response->assertStatus(422);
        $response->assertJson([
            'errors' => [
                'name' => ['The name field is required.'],
                'route_path' => ['The route path field is required.'],
                'page_type' => ['The page type field is required.'],
            ],
        ]);
    }

    public function test_create_fails_name_wrong_type()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->json('POST', $this->path.$user->id.'/pages', [
            'name' => 5,
            'route_path' => 'test',
            'page_type' => 'list',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'errors' => [
                'name' => ['The name must be a string.'],
            ],
        ]);
    }

    public function test_create_fails_route_path_wrong_type()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->json('POST', $this->path.$user->id.'/pages', [
            'name' => 'Test',
            'route_path' => 5,
            'page_type' => 'list',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'errors' => [
                'route_path' => ['The route path must be a string.'],
            ],
        ]);
    }

    public function test_create_fails_page_type_wrong_type()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->json('POST', $this->path.$user->id.'/pages', [
            'name' => 'Test',
            'route_path' => 'test',
            'page_type' => 5,
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'errors' => [
                'page_type' => ['The page type must be a string.'],
            ],
        ]);
    }

    public function test_create_fails_name_too_long()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->json('POST', $this->path.$user->id.'/pages', [
            'name' => str_repeat('a', 101),
            'route_path' => 'test',
            'page_type' => 'list',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'errors' => [
                'name' => ['The name may not be greater than 100 characters.'],
            ],
        ]);
    }

    public function test_create_fails_route_path_too_long()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->json('POST', $this->path.$user->id.'/pages', [
            'name' => 'Test',
            'route_path' => str_repeat('a', 101),
            'page_type' => 'list',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'errors' => [
                'route_path' => ['The route path may not be greater than 100 characters.'],
            ],
        ]);
    }

    public function test_create_fails_slug_wrong_type()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->json('POST', $this->path.$user->id.'/pages', [
            'name' => 'Test',
            'route_path' => 'test',
            'page_type' => 'list',
            'slug' => 5,
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'errors' => [
                'slug' => ['The slug must be a string.'],
            ],
        ]);
    }

    public function test_create_fails_slug_too_long()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->json('POST', $this->path.$user->id.'/pages', [
            'name' => 'Test',
            'route_path' => 'test',
            'page_type' => 'list',
            'slug' => str_repeat('a', 51),
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'errors' => [
                'slug' => ['The slug may not be greater than 50 characters.'],
            ],
        ]);
    }

    public function test_create_fails_slug_regex_violation()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->json('POST', $this->path.$user->id.'/pages', [
            'name' => 'Test',
            'route_path' => 'test',
            'page_type' => 'list',
            'slug' => 'INVALID SLUG!',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'errors' => [
                'slug' => ['The slug format is invalid.'],
            ],
        ]);
    }

    public function test_create_fails_icon_wrong_type()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->json('POST', $this->path.$user->id.'/pages', [
            'name' => 'Test',
            'route_path' => 'test',
            'page_type' => 'list',
            'icon' => 5,
        ]);

        $response->assertStatus(422);
    }

    public function test_create_fails_icon_too_long()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->json('POST', $this->path.$user->id.'/pages', [
            'name' => 'Test',
            'route_path' => 'test',
            'page_type' => 'list',
            'icon' => str_repeat('a', 51),
        ]);

        $response->assertStatus(422);
    }

    public function test_create_fails_color_wrong_type()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->json('POST', $this->path.$user->id.'/pages', [
            'name' => 'Test',
            'route_path' => 'test',
            'page_type' => 'list',
            'color' => 5,
        ]);

        $response->assertStatus(422);
    }

    public function test_create_fails_color_too_long()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->json('POST', $this->path.$user->id.'/pages', [
            'name' => 'Test',
            'route_path' => 'test',
            'page_type' => 'list',
            'color' => str_repeat('a', 8),
        ]);

        $response->assertStatus(422);
    }

    public function test_create_fails_color_regex_violation()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->json('POST', $this->path.$user->id.'/pages', [
            'name' => 'Test',
            'route_path' => 'test',
            'page_type' => 'list',
            'color' => 'red',
        ]);

        $response->assertStatus(422);
    }

    public function test_create_fails_display_order_wrong_type()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->json('POST', $this->path.$user->id.'/pages', [
            'name' => 'Test',
            'route_path' => 'test',
            'page_type' => 'list',
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

        $response = $this->json('POST', $this->path.$user->id.'/pages', [
            'name' => 'Test',
            'route_path' => 'test',
            'page_type' => 'list',
            'display_order' => -1,
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'errors' => [
                'display_order' => ['The display order must be at least 0.'],
            ],
        ]);
    }

    public function test_create_fails_parent_page_id_wrong_type()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->json('POST', $this->path.$user->id.'/pages', [
            'name' => 'Test',
            'route_path' => 'test',
            'page_type' => 'list',
            'parent_page_id' => 'abc',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'errors' => [
                'parent_page_id' => ['The parent page id must be an integer.'],
            ],
        ]);
    }

    public function test_create_fails_config_json_wrong_type()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->json('POST', $this->path.$user->id.'/pages', [
            'name' => 'Test',
            'route_path' => 'test',
            'page_type' => 'list',
            'config_json' => 'not_array',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'errors' => [
                'config_json' => ['The config json must be an array.'],
            ],
        ]);
    }

    public function test_create_fails_is_visible_wrong_type()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->json('POST', $this->path.$user->id.'/pages', [
            'name' => 'Test',
            'route_path' => 'test',
            'page_type' => 'list',
            'is_visible' => 'notbool',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'errors' => [
                'is_visible' => ['The is visible field must be true or false.'],
            ],
        ]);
    }
}
