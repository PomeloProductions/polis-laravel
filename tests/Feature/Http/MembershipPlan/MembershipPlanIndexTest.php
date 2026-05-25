<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\Http\MembershipPlan;

use App\Models\Role;
use App\Models\Subscription\MembershipPlan;
use Polis\Tests\DatabaseSetupTrait;
use Polis\Tests\TestCase;
use Polis\Tests\Traits\MocksApplicationLog;

/**
 * Class MembershipPlanIndexTest
 */
final class MembershipPlanIndexTest extends TestCase
{
    use DatabaseSetupTrait, MocksApplicationLog;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();
        $this->mockApplicationLog();
    }

    public function test_not_logged_in_user_blocked(): void
    {
        $response = $this->json('GET', '/v1/membership-plans');
        $response->assertStatus(403);
    }

    public function test_get_pagination_empty(): void
    {
        $this->actAs(Role::APP_USER);
        $response = $this->json('GET', '/v1/membership-plans');

        $response->assertStatus(200);
        $response->assertJson([
            'total' => 0,
            'data' => [],
        ]);
    }

    public function test_get_pagination_result(): void
    {
        $this->actAs(Role::APP_USER);
        MembershipPlan::factory()->count(15)->create();

        // first page
        $response = $this->json('GET', '/v1/membership-plans');
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
                    '*' => array_keys((new MembershipPlan)->toArray()),
                ],
            ]);
        $response->assertStatus(200);

        // second page
        $response = $this->json('GET', '/v1/membership-plans?page=2');
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
                    '*' => array_keys((new MembershipPlan)->toArray()),
                ],
            ]);
        $response->assertStatus(200);

        // page with limit
        $response = $this->json('GET', '/v1/membership-plans?page=2&limit=5');
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
                    '*' => array_keys((new MembershipPlan)->toArray()),
                ],
            ]);
        $response->assertStatus(200);
    }
}
