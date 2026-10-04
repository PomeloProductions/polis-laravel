<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\Http\Category;

use App\Models\Role;
use Polis\Tests\Application\ApplicationTestCase;
use Polis\Tests\Traits\MocksApplicationLog;
use Polis\Tests\Traits\RolesTesting;

/**
 * Class MembershipPlanCreateTest
 */
final class CategoryCreateTest extends ApplicationTestCase
{
    use MocksApplicationLog, RolesTesting;

    private $route = '/v1/categories';

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();
        $this->mockApplicationLog();
    }

    public function test_not_logged_in_user_blocked(): void
    {
        $response = $this->json('POST', $this->route);
        $response->assertStatus(403);
    }

    public function test_create_successful(): void
    {
        $this->actAs(Role::APP_USER);

        $properties = [
            'name' => 'A Category',
        ];

        $response = $this->json('POST', $this->route, $properties);

        $response->assertStatus(201);

        $response->assertJson($properties);
    }

    public function test_create_fails_missing_required_fields(): void
    {
        $this->actAs(Role::APP_USER);

        $response = $this->json('POST', $this->route);

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'Sorry, something went wrong.',
            'errors' => [
                'name' => ['The name field is required.'],
            ],
        ]);
    }

    public function test_create_fails_invalid_string_fields(): void
    {
        $this->actAs(Role::APP_USER);

        $data = [
            'name' => 5435,
            'description' => 5,
        ];

        $response = $this->json('POST', $this->route, $data);

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'Sorry, something went wrong.',
            'errors' => [
                'name' => ['The name must be a string.'],
                'description' => ['The description must be a string.'],
            ],
        ]);
    }

    public function test_create_fails_parent_id_wrong_type(): void
    {
        $this->actAs(Role::APP_USER);

        $response = $this->json('POST', $this->route, [
            'name' => 'Test Category',
            'parent_id' => 'abc',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'Sorry, something went wrong.',
            'errors' => [
                'parent_id' => ['The parent id must be an integer.'],
            ],
        ]);
    }

    public function test_create_fails_parent_id_nonexistent(): void
    {
        $this->actAs(Role::APP_USER);

        $response = $this->json('POST', $this->route, [
            'name' => 'Test Category',
            'parent_id' => 99999,
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'Sorry, something went wrong.',
            'errors' => [
                'parent_id' => ['The selected parent id is invalid.'],
            ],
        ]);
    }

    public function test_create_fails_color_wrong_type(): void
    {
        $this->actAs(Role::APP_USER);

        $response = $this->json('POST', $this->route, [
            'name' => 'Test Category',
            'color' => 5,
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'Sorry, something went wrong.',
            'errors' => [
                'color' => ['The color must be a string.'],
            ],
        ]);
    }

    public function test_create_fails_color_too_long(): void
    {
        $this->actAs(Role::APP_USER);

        $response = $this->json('POST', $this->route, [
            'name' => 'Test Category',
            'color' => str_repeat('a', 17),
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'Sorry, something went wrong.',
            'errors' => [
                'color' => ['The color may not be greater than 16 characters.'],
            ],
        ]);
    }
}
