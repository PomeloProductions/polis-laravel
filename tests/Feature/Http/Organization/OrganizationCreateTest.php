<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\Http\Organization;

use App\Models\Role;
use Polis\Tests\Application\ApplicationTestCase;
use Polis\Tests\Traits\MocksApplicationLog;
use Polis\Tests\Traits\RolesTesting;

/**
 * Class OrganizationCreateTest
 */
final class OrganizationCreateTest extends ApplicationTestCase
{
    use MocksApplicationLog, RolesTesting;

    private $route = '/v1/organizations';

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
        $this->actAs(Role::SUPER_ADMIN);

        $properties = [
            'name' => 'An Organization',
        ];

        $response = $this->json('POST', $this->route, $properties);

        $response->assertStatus(201);

        $response->assertJson($properties);
    }

    public function test_create_fails_missing_required_fields(): void
    {
        $this->actAs(Role::SUPER_ADMIN);

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
        $this->actAs(Role::SUPER_ADMIN);

        $data = [
            'name' => 5435,
        ];

        $response = $this->json('POST', $this->route, $data);

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'Sorry, something went wrong.',
            'errors' => [
                'name' => ['The name must be a string.'],
            ],
        ]);
    }

    public function test_create_fails_string_too_long(): void
    {
        $this->actAs(Role::SUPER_ADMIN);

        $data = [
            'name' => str_repeat('a', 121),
        ];

        $response = $this->json('POST', $this->route, $data);

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'Sorry, something went wrong.',
            'errors' => [
                'name' => ['The name may not be greater than 120 characters.'],
            ],
        ]);
    }
}
