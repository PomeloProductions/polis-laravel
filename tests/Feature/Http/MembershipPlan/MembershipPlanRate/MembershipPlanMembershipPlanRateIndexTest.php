<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\Http\MembershipPlan\MembershipPlanRate;

use App\Models\Role;
use App\Models\Subscription\MembershipPlan;
use App\Models\Subscription\MembershipPlanRate;
use Polis\Tests\Application\ApplicationTestCase;
use Polis\Tests\Traits\MocksApplicationLog;
use Polis\Tests\Traits\RolesTesting;

/**
 * Class MembershipPlanMembershipPlanRateIndexTest
 */
final class MembershipPlanMembershipPlanRateIndexTest extends ApplicationTestCase
{
    use MocksApplicationLog, RolesTesting;

    /**
     * @var string
     */
    private $route;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();
        $this->mockApplicationLog();
    }

    /**
     * Sets up the proper route for the request
     */
    private function setupRoute(int $id)
    {
        $this->route = '/v1/membership-plans/'.$id.'/rates';
    }

    public function test_not_logged_in_user_blocked(): void
    {
        $model = MembershipPlan::factory()->create();
        $this->setupRoute($model->id);
        $response = $this->json('GET', $this->route);
        $response->assertStatus(403);
    }

    public function test_non_admin_users_blocked(): void
    {
        foreach ($this->rolesWithoutAdmins() as $role) {
            $this->actAs($role);
            $model = MembershipPlan::factory()->create();
            $this->setupRoute($model->id);
            $response = $this->json('GET', $this->route);

            $response->assertStatus(403);
        }
    }

    public function test_get_pagination_empty(): void
    {
        $this->actAs(Role::SUPER_ADMIN);
        $model = MembershipPlan::factory()->create();
        $this->setupRoute($model->id);
        $response = $this->json('GET', $this->route);

        $response->assertStatus(200);
        $response->assertJson([
            'total' => 0,
            'data' => [],
        ]);
    }

    public function test_get_pagination_result(): void
    {
        $this->actAs(Role::SUPER_ADMIN);
        $model = MembershipPlan::factory()->create();
        $this->setupRoute($model->id);
        MembershipPlanRate::factory()->count(15)->create([
            'membership_plan_id' => $model->id,
        ]);
        MembershipPlanRate::factory()->count(3)->create();

        // first page
        $response = $this->json('GET', $this->route);
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
                    '*' => array_keys((new MembershipPlanRate)->toArray()),
                ],
            ]);
        $response->assertStatus(200);

        // second page
        $response = $this->json('GET', $this->route.'?page=2');
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
                    '*' => array_keys((new MembershipPlanRate)->toArray()),
                ],
            ]);
        $response->assertStatus(200);

        // page with limit
        $response = $this->json('GET', $this->route.'?page=2&limit=5');
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
                    '*' => array_keys((new MembershipPlanRate)->toArray()),
                ],
            ]);
        $response->assertStatus(200);
    }
}
