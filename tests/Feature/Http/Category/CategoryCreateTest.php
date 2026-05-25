<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\Http\Category;

use App\Models\Role;
use Polis\Tests\DatabaseSetupTrait;
use Polis\Tests\TestCase;
use Polis\Tests\Traits\MocksApplicationLog;
use Polis\Tests\Traits\RolesTesting;

/**
 * Class MembershipPlanCreateTest
 */
final class CategoryCreateTest extends TestCase
{
    use DatabaseSetupTrait, MocksApplicationLog, RolesTesting;

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

        $response->assertStatus(400);
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

        $response->assertStatus(400);
        $response->assertJson([
            'message' => 'Sorry, something went wrong.',
            'errors' => [
                'name' => ['The name must be a string.'],
                'description' => ['The description must be a string.'],
            ],
        ]);
    }
}
