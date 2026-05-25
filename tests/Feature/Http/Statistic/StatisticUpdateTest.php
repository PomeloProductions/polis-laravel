<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\Http\Statistic;

use App\Models\Role;
use App\Models\Statistic\Statistic;
use Polis\Tests\DatabaseSetupTrait;
use Polis\Tests\TestCase;
use Polis\Tests\Traits\MocksApplicationLog;
use Polis\Tests\Traits\RolesTesting;

/**
 * Class StatisticUpdateTest
 */
class StatisticUpdateTest extends TestCase
{
    use DatabaseSetupTrait, MocksApplicationLog, RolesTesting;

    const BASE_ROUTE = '/v1/statistics/';

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();
        $this->mockApplicationLog();
    }

    public function test_not_logged_in_user_blocked()
    {
        $statistic = Statistic::factory()->create();
        $response = $this->json('PATCH', static::BASE_ROUTE.$statistic->id);
        $response->assertStatus(403);
    }

    public function test_not_admin_user_blocked()
    {
        foreach ($this->rolesWithoutAdmins([Role::CONTENT_EDITOR, Role::SUPPORT_STAFF]) as $role) {
            $this->actAs($role);
            $statistic = Statistic::factory()->create();
            $response = $this->json('PATCH', static::BASE_ROUTE.$statistic->id);
            $response->assertStatus(403);
        }
    }

    public function test_patch_successful()
    {
        $this->actAs(Role::SUPER_ADMIN);

        /** @var Statistic $statistic */
        $statistic = Statistic::factory()->create([
            'name' => 'Test Stat',
        ]);

        $data = [
            'name' => 'Test Statistic',
        ];

        $response = $this->json('PATCH', static::BASE_ROUTE.$statistic->id, $data);
        $response->assertStatus(200);
        $response->assertJson($data);

        /** @var Statistic $updated */
        $updated = Statistic::find($statistic->id);

        $this->assertEquals('Test Statistic', $updated->name);
    }

    public function test_patch_not_found_fails()
    {
        $this->actAs(Role::SUPER_ADMIN);

        $response = $this->json('PATCH', static::BASE_ROUTE.'5')
            ->assertExactJson([
                'message' => 'This item was not found.',
            ]);
        $response->assertStatus(404);
    }

    public function test_patch_invalid_id_fails()
    {
        $this->actAs(Role::SUPER_ADMIN);

        $response = $this->json('PATCH', static::BASE_ROUTE.'/b')
            ->assertExactJson([
                'message' => 'This path was not found.',
            ]);
        $response->assertStatus(404);
    }

    public function test_patch_successful_no_fields()
    {
        $this->actAs(Role::SUPER_ADMIN);

        $statistic = Statistic::factory()->create([
            'name' => 'Test Gift Pack',
        ]);

        $response = $this->json('PATCH', static::BASE_ROUTE.$statistic->id, []);

        $response->assertStatus(200);
    }

    public function test_patch_fails_including_not_preset_fields()
    {
        $statistic = Statistic::factory()->create();

        $this->actAs(Role::SUPER_ADMIN);
        $response = $this->json('PATCH', static::BASE_ROUTE.$statistic->id, [
            'model' => 'character',
            'relation' => 'active',
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'message' => 'Sorry, something went wrong.',
            'errors' => [
                'model' => ['The model field is not allowed or can not be set for this request.'],
                'relation' => ['The relation field is not allowed or can not be set for this request.'],
            ],
        ]);
    }

    public function test_patch_fails_invalid_string_fields()
    {
        $this->actAs(Role::SUPER_ADMIN);

        $data = [
            'name' => 5,
        ];

        $statistic = Statistic::factory()->create();

        $response = $this->json('PATCH', static::BASE_ROUTE.$statistic->id, $data);

        $response->assertStatus(400);
        $response->assertJson([
            'message' => 'Sorry, something went wrong.',
            'errors' => [
                'name' => ['The name must be a string.'],
            ],
        ]);
    }

    public function test_patch_fails_invalid_boolean_fields()
    {
        $this->actAs(Role::SUPER_ADMIN);

        $data = [
            'public' => 'hi',
        ];

        $statistic = Statistic::factory()->create();

        $response = $this->json('PATCH', static::BASE_ROUTE.$statistic->id, $data);

        $response->assertStatus(400);
        $response->assertJson([
            'message' => 'Sorry, something went wrong.',
            'errors' => [
                'public' => ['The public field must be true or false.'],
            ],
        ]);
    }

    public function test_patch_fails_invalid_array_fields()
    {
        $this->actAs(Role::SUPER_ADMIN);

        $data = [
            'statistic_filters' => 'hi',
        ];

        $statistic = Statistic::factory()->create();

        $response = $this->json('PATCH', static::BASE_ROUTE.$statistic->id, $data);

        $response->assertStatus(400);
        $response->assertJson([
            'message' => 'Sorry, something went wrong.',
            'errors' => [
                'statistic_filters' => ['The statistic filters must be an array.'],
            ],
        ]);
    }

    public function test_patch_fails_invalid_filter_array_fields()
    {
        $this->actAs(Role::SUPER_ADMIN);

        $data = [
            'statistic_filters' => [
                'ho',
            ],
        ];

        $statistic = Statistic::factory()->create();

        $response = $this->json('PATCH', static::BASE_ROUTE.$statistic->id, $data);

        $response->assertStatus(400);
        $response->assertJson([
            'message' => 'Sorry, something went wrong.',
            'errors' => [
                'statistic_filters.0' => ['The statistic_filters.0 must be an array.'],
            ],
        ]);
    }

    public function test_patch_fails_invalid_filter_required_fields()
    {
        $this->actAs(Role::SUPER_ADMIN);

        $data = [
            'statistic_filters' => [
                [],
            ],
        ];

        $statistic = Statistic::factory()->create();

        $response = $this->json('PATCH', static::BASE_ROUTE.$statistic->id, $data);

        $response->assertStatus(400);
        $response->assertJson([
            'message' => 'Sorry, something went wrong.',
            'errors' => [
                'statistic_filters.0.field' => ['The statistic_filters.0.field field is required.'],
                'statistic_filters.0.operator' => ['The statistic_filters.0.operator field is required.'],
            ],
        ]);
    }

    public function test_patch_fails_invalid_filter_string_fields()
    {
        $this->actAs(Role::SUPER_ADMIN);

        $data = [
            'statistic_filters' => [
                [
                    'field' => 1,
                    'operator' => 1,
                    'value' => 1,
                ],
            ],
        ];

        $statistic = Statistic::factory()->create();

        $response = $this->json('PATCH', static::BASE_ROUTE.$statistic->id, $data);

        $response->assertStatus(400);
        $response->assertJson([
            'message' => 'Sorry, something went wrong.',
            'errors' => [
                'statistic_filters.0.field' => ['The statistic_filters.0.field must be a string.'],
                'statistic_filters.0.operator' => ['The statistic_filters.0.operator must be a string.'],
                'statistic_filters.0.value' => ['The statistic_filters.0.value must be a string.'],
            ],
        ]);
    }
}
