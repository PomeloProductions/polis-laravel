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
 * Class MembershipPlanDeleteTest
 */
final class MembershipPlanDeleteTest extends TestCase
{
    use DatabaseSetupTrait, MocksApplicationLog, RolesTesting;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();
        $this->mockApplicationLog();
    }

    public function test_not_logged_in_user_blocked(): void
    {
        $model = MembershipPlan::factory()->create();
        $response = $this->json('DELETE', '/v1/membership-plans/'.$model->id);
        $response->assertStatus(403);
    }

    public function test_non_admin_user_blocked(): void
    {
        foreach ($this->rolesWithoutAdmins() as $role) {
            $this->actAs($role);
            $model = MembershipPlan::factory()->create();
            $response = $this->json('DELETE', '/v1/membership-plans/'.$model->id);
            $response->assertStatus(403);
        }
    }

    public function test_delete_single(): void
    {
        $this->actAs(Role::SUPER_ADMIN);

        $model = MembershipPlan::factory()->create();

        $response = $this->json('DELETE', '/v1/membership-plans/'.$model->id);

        $response->assertStatus(204);
        $this->assertEquals(0, MembershipPlan::count());
    }

    public function test_delete_single_invalid_id_fails(): void
    {
        $this->actAs(Role::SUPER_ADMIN);

        $response = $this->json('DELETE', '/v1/membership-plans/a')
            ->assertSimilarJson([
                'message' => 'This path was not found.',
            ]);
        $response->assertStatus(404);
    }

    public function test_delete_single_not_found_fails(): void
    {
        $this->actAs(Role::SUPER_ADMIN);

        $response = $this->json('DELETE', '/v1/membership-plans/1')
            ->assertSimilarJson([
                'message' => 'This item was not found.',
            ]);
        $response->assertStatus(404);
    }
}
