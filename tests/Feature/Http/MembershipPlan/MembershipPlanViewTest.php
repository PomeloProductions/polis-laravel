<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\Http\MembershipPlan;

use App\Models\Role;
use App\Models\Subscription\MembershipPlan;
use Polis\Tests\DatabaseSetupTrait;
use Polis\Tests\TestCase;
use Polis\Tests\Traits\MocksApplicationLog;

/**
 * Class MembershipPlanViewTest
 */
final class MembershipPlanViewTest extends TestCase
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
        $model = MembershipPlan::factory()->create();
        $response = $this->json('GET', '/v1/membership-plans/'.$model->id);
        $response->assertStatus(403);
    }

    public function test_get_single_success(): void
    {
        $this->actAs(Role::APP_USER);
        /** @var MembershipPlan $model */
        $model = MembershipPlan::factory()->create([
            'id' => 1,
        ]);

        $response = $this->json('GET', '/v1/membership-plans/1');

        $response->assertStatus(200);
        $response->assertJson($model->toArray());
    }

    public function test_get_single_not_found_fails(): void
    {
        $this->actAs(Role::APP_USER);
        $response = $this->json('GET', '/v1/membership-plans/1')
            ->assertSimilarJson([
                'message' => 'This item was not found.',
            ]);
        $response->assertStatus(404);
    }

    public function test_get_single_invalid_id_fails(): void
    {
        $this->actAs(Role::APP_USER);
        $response = $this->json('GET', '/v1/membership-plans/a')
            ->assertSimilarJson([
                'message' => 'This path was not found.',
            ]);
        $response->assertStatus(404);
    }
}
