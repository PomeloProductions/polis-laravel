<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\Http\MembershipPlan;

use App\Models\Role;
use App\Models\Subscription\MembershipPlan;
use Polis\Tests\DatabaseSetupTrait;
use Polis\Tests\TestCase;
use Polis\Tests\Traits\MocksApplicationLog;
use Polis\Tests\Traits\RolesTesting;

/**
 * Class MembershipPlanCreateTest
 */
final class MembershipPlanCreateTest extends TestCase
{
    use DatabaseSetupTrait, MocksApplicationLog, RolesTesting;

    private $route = '/v1/membership-plans';

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

    public function test_not_admin_user_blocked(): void
    {
        foreach ($this->rolesWithoutAdmins() as $role) {
            $this->actAs($role);
            $response = $this->json('POST', $this->route);
            $response->assertStatus(403);
        }
    }

    public function test_create_successful(): void
    {
        $this->actAs(Role::SUPER_ADMIN);

        $properties = [
            'name' => 'Hellow',
            'entity_type' => 'user',
            'duration' => MembershipPlan::DURATION_LIFETIME,
            'current_cost' => 60.00,
            'default' => true,
        ];

        $response = $this->json('POST', $this->route, $properties);

        $response->assertStatus(201);

        $response->assertJson($properties);
    }

    public function test_create_fails_missing_required_fields(): void
    {
        $this->actAs(Role::SUPER_ADMIN);

        $response = $this->json('POST', $this->route);

        $response->assertStatus(400);
        $response->assertJson([
            'message' => 'Sorry, something went wrong.',
            'errors' => [
                'name' => ['The name field is required.'],
                'entity_type' => ['The entity type field is required.'],
                'current_cost' => ['The current cost field is required.'],
                'duration' => ['The duration field is required.'],
            ],
        ]);
    }

    public function test_create_fails_invalid_array_fields(): void
    {
        $this->actAs(Role::SUPER_ADMIN);

        $data = [
            'features' => 'hi',
        ];

        $response = $this->json('POST', $this->route, $data);

        $response->assertStatus(400);
        $response->assertJson([
            'message' => 'Sorry, something went wrong.',
            'errors' => [
                'features' => ['The features must be an array.'],
            ],
        ]);
    }

    public function test_create_fails_invalid_numeric_fields(): void
    {
        $this->actAs(Role::SUPER_ADMIN);
        $response = $this->json('POST', $this->route, [
            'current_cost' => 'hi',
            'trial_period' => 'hi',
            'features' => ['hi'],
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'message' => 'Sorry, something went wrong.',
            'errors' => [
                'current_cost' => ['The current cost must be a number.'],
                'trial_period' => ['The trial period must be an integer.'],
                'features.0' => ['The features.0 must be a number.'],
            ],
        ]);
    }

    public function test_create_fails_invalid_numeric_minimums(): void
    {
        $this->actAs(Role::SUPER_ADMIN);
        $response = $this->json('POST', $this->route, [
            'current_cost' => -1,
            'trial_period' => -1,
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'message' => 'Sorry, something went wrong.',
            'errors' => [
                'current_cost' => ['The current cost must be at least 0.00.'],
                'trial_period' => ['The trial period must be at least 0.'],
            ],
        ]);
    }

    public function test_create_fails_invalid_string_fields(): void
    {
        $this->actAs(Role::SUPER_ADMIN);

        $data = [
            'name' => 5435,
            'entity_type' => 5435,
            'description' => 5435,
            'duration' => 5,
        ];

        $response = $this->json('POST', $this->route, $data);

        $response->assertStatus(400);
        $response->assertJson([
            'message' => 'Sorry, something went wrong.',
            'errors' => [
                'name' => ['The name must be a string.'],
                'entity_type' => ['The entity type must be a string.'],
                'description' => ['The description must be a string.'],
                'duration' => ['The duration must be a string.'],
            ],
        ]);
    }

    public function test_create_fails_invalid_boolean_fields(): void
    {
        $this->actAs(Role::SUPER_ADMIN);

        $data = [
            'default' => 'hello',
        ];

        $response = $this->json('POST', $this->route, $data);

        $response->assertStatus(400);
        $response->assertJson([
            'message' => 'Sorry, something went wrong.',
            'errors' => [
                'default' => ['The default field must be true or false.'],
            ],
        ]);
    }

    public function test_create_fails_invalid_model_fields(): void
    {
        $this->actAs(Role::SUPER_ADMIN);
        $response = $this->json('POST', $this->route, [
            'features' => [1425],
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'message' => 'Sorry, something went wrong.',
            'errors' => [
                'features.0' => ['The selected features.0 is invalid.'],
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

        $response->assertStatus(400);
        $response->assertJson([
            'message' => 'Sorry, something went wrong.',
            'errors' => [
                'name' => ['The name may not be greater than 120 characters.'],
            ],
        ]);
    }

    public function test_create_fails_invalid_enum_fields(): void
    {
        $this->actAs(Role::SUPER_ADMIN);

        $data = [
            'duration' => 'hi',
            'entity_type' => 'hi',
        ];

        $response = $this->json('POST', $this->route, $data);

        $response->assertStatus(400);
        $response->assertJson([
            'message' => 'Sorry, something went wrong.',
            'errors' => [
                'duration' => ['The selected duration is invalid.'],
                'entity_type' => ['The selected entity type is invalid.'],
            ],
        ]);
    }
}
