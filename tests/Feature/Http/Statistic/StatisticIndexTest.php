<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\Http\Statistic;

use App\Models\Role;
use App\Models\Statistic\Statistic;
use Polis\Tests\Application\ApplicationTestCase;
use Polis\Tests\Traits\MocksApplicationLog;

/**
 * Class StatisticIndexTest
 */
class StatisticIndexTest extends ApplicationTestCase
{
    use MocksApplicationLog;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();
        $this->mockApplicationLog();
    }

    public function test_not_logged_in_user_blocked()
    {
        $response = $this->json('GET', '/v1/statistics');
        $response->assertStatus(403);
    }

    public function test_get_pagination_empty()
    {
        $this->actAs(Role::APP_USER);
        $response = $this->json('GET', '/v1/statistics');
        $response->assertStatus(200);
        $response->assertJson([
            'total' => 0,
            'data' => [],
        ]);
    }

    public function test_get_pagination_result()
    {
        $this->actAs(Role::APP_USER);
        Statistic::factory()->count(15)->create();

        // first page
        $response = $this->json('GET', '/v1/statistics');
        $response->assertJson([
            'total' => 15,
            'current_page' => 1,
            'per_page' => 10,
            'from' => 1,
            'to' => 10,
            'last_page' => 2,
        ])
            ->assertJsonStructure([
                'data' => [
                    '*' => array_keys((new Statistic)->toArray()),
                ],
            ]);
        $response->assertStatus(200);

        // second page
        $response = $this->json('GET', '/v1/statistics?page=2');
        $response->assertJson([
            'total' => 15,
            'current_page' => 2,
            'per_page' => 10,
            'from' => 11,
            'to' => 15,
            'last_page' => 2,
        ])
            ->assertJsonStructure([
                'data' => [
                    '*' => array_keys((new Statistic)->toArray()),
                ],
            ]);
        $response->assertStatus(200);

        // page with limit
        $response = $this->json('GET', '/v1/statistics?page=2&limit=5');
        $response->assertJson([
            'total' => 15,
            'current_page' => 2,
            'per_page' => 5,
            'from' => 6,
            'to' => 10,
            'last_page' => 3,
        ])
            ->assertJsonStructure([
                'data' => [
                    '*' => array_keys((new Statistic)->toArray()),
                ],
            ]);
        $response->assertStatus(200);
    }
}
