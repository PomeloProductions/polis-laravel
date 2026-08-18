<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\Http\Statistic;

use App\Models\Role;
use App\Models\Statistic\Statistic;
use Polis\Tests\Application\ApplicationTestCase;
use Polis\Tests\Traits\RolesTesting;

/**
 * Class StatisticCreateTest
 */
class StatisticCreateTest extends ApplicationTestCase
{
    use RolesTesting;

    private $route = '/v1/statistics';

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();
    }

    public function test_not_logged_in_user_blocked()
    {
        $response = $this->json('POST', $this->route);

        $response->assertStatus(403);
    }

    public function test_not_authorized_user_blocked()
    {
        $this->actAsUser();
        $response = $this->json('POST', $this->route);

        $response->assertStatus(403);
    }

    public function test_create_success_without_statistic_filters()
    {
        $this->actAs(Role::CONTENT_EDITOR);

        $properties = [
            'name' => 'Test Statistic',
            'model' => 'collection',
            'relation' => 'collectionItems',
            'public' => true,
        ];

        $response = $this->json('POST', $this->route, $properties);
        $response->assertStatus(201);
        $response->assertJsonFragment($properties);
    }

    public function test_create_success_with_statistic_filters()
    {
        $this->actAs(Role::CONTENT_EDITOR);

        $properties = [
            'name' => 'Test Statistic',
            'model' => 'collection',
            'relation' => 'collectionItems',
            'public' => true,
            'statistic_filters' => [
                [
                    'field' => 'active',
                    'operator' => '=',
                    'value' => '1',
                ],
            ],
        ];

        $response = $this->json('POST', $this->route, $properties);
        $response->assertStatus(201);
        unset($properties['statistic_filters']);
        $response->assertJsonFragment($properties);

        /** @var Statistic $created */
        $created = Statistic::first();
        $this->assertCount(1, $created->statisticFilters);
    }

    public function test_create_fails_validation()
    {
        $this->actAs(Role::CONTENT_EDITOR);

        $response = $this->json('POST', $this->route, [
            'name' => '',
            'model' => '',
            'relation' => '',
            'public' => 'yes',
            'statistic_filters' => 'hi',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'name' => ['The name field is required.'],
            'model' => ['The model field is required.'],
            'relation' => ['The relation field is required.'],
            'public' => ['The public field must be true or false.'],
            'statistic_filters' => ['The statistic filters must be an array.'],
        ]);
    }

    public function test_create_fails_statistic_filter_validation()
    {
        $this->actAs(Role::CONTENT_EDITOR);

        $response = $this->json('POST', $this->route, [
            'name' => 'Test',
            'model' => 'collection',
            'relation' => '',
            'statistic_filters' => [
                'not an array',
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'statistic_filters.0' => ['The statistic_filters.0 must be an array.'],
            'statistic_filters.0.field' => ['The statistic_filters.0.field field is required.'],
            'statistic_filters.0.operator' => ['The statistic_filters.0.operator field is required.'],
        ]);

        $response = $this->json('POST', $this->route, [
            'name' => 'Test',
            'model' => 'collection',
            'relation' => 'collectionItems',
            'statistic_filters' => [
                [],
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'statistic_filters.0.field' => ['The statistic_filters.0.field field is required.'],
            'statistic_filters.0.operator' => ['The statistic_filters.0.operator field is required.'],
        ]);

        $response = $this->json('POST', $this->route, [
            'name' => 'Test',
            'model' => 'collection',
            'relation' => 'collectionItems',
            'statistic_filters' => [
                [
                    'field' => 123,
                    'operator' => 456,
                    'value' => 789,
                ],
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'statistic_filters.0.field' => ['The statistic_filters.0.field must be a string.'],
            'statistic_filters.0.operator' => ['The statistic_filters.0.operator must be a string.'],
            'statistic_filters.0.value' => ['The statistic_filters.0.value must be a string.'],
        ]);
    }
}
