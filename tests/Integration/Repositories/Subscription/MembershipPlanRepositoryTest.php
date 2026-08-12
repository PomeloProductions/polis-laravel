<?php

declare(strict_types=1);

namespace Polis\Tests\Integration\Repositories\Subscription;

use App\Models\Feature;
use App\Models\Subscription\MembershipPlan;
use App\Models\Subscription\MembershipPlanRate;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Polis\Repositories\Subscription\MembershipPlanRateRepository;
use Polis\Repositories\Subscription\MembershipPlanRepository;
use Polis\Tests\Application\ApplicationTestCase;
use Polis\Tests\Traits\MocksApplicationLog;

/**
 * Class MembershipPlanRepositoryTest
 */
final class MembershipPlanRepositoryTest extends ApplicationTestCase
{
    use MocksApplicationLog;

    /**
     * @var MembershipPlanRepository
     */
    protected $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();

        $this->repository = new MembershipPlanRepository(
            new MembershipPlan,
            $this->getGenericLogMock(),
            new MembershipPlanRateRepository(
                new MembershipPlanRate,
                $this->getGenericLogMock(),
            ),
        );
    }

    public function test_find_all_success(): void
    {
        foreach (MembershipPlan::all() as $model) {
            $model->delete();
        }

        MembershipPlan::factory()->count(5)->create();
        $items = $this->repository->findAll();
        $this->assertCount(5, $items);
    }

    public function test_find_all_empty(): void
    {
        foreach (MembershipPlan::all() as $model) {
            $model->delete();
        }

        $items = $this->repository->findAll();
        $this->assertEmpty($items);
    }

    public function test_find_or_fail_success(): void
    {
        $model = MembershipPlan::factory()->create();

        $foundModel = $this->repository->findOrFail($model->id);
        $this->assertEquals($model->id, $foundModel->id);
    }

    public function test_find_or_fail_fails(): void
    {
        MembershipPlan::factory()->create(['id' => 19]);

        $this->expectException(ModelNotFoundException::class);
        $this->repository->findOrFail(20);
    }

    public function test_create_success(): void
    {
        /** @var MembershipPlan $membershipPlan */
        $membershipPlan = $this->repository->create([
            'duration' => MembershipPlan::DURATION_YEAR,
            'name' => 'a plan',
        ]);

        $this->assertEquals(MembershipPlan::DURATION_YEAR, $membershipPlan->duration);
        $this->assertEquals('a plan', $membershipPlan->name);
    }

    public function test_create_success_with_cost(): void
    {
        /** @var MembershipPlan $membershipPlan */
        $membershipPlan = $this->repository->create([
            'duration' => MembershipPlan::DURATION_YEAR,
            'name' => 'a plan',
            'current_cost' => 10.12,
        ]);

        $this->assertEquals(MembershipPlan::DURATION_YEAR, $membershipPlan->duration);
        $this->assertEquals('a plan', $membershipPlan->name);
        $this->assertEquals(10.12, $membershipPlan->current_cost);
    }

    public function test_create_success_with_features(): void
    {
        /** @var MembershipPlan $membershipPlan */
        $membershipPlan = $this->repository->create([
            'duration' => MembershipPlan::DURATION_YEAR,
            'name' => 'a plan',
            'features' => Feature::factory()->count(3)->create()->pluck('id'),
        ]);

        $this->assertEquals(MembershipPlan::DURATION_YEAR, $membershipPlan->duration);
        $this->assertEquals('a plan', $membershipPlan->name);
        $this->assertCount(3, $membershipPlan->features);
    }

    public function test_update_success(): void
    {
        $model = MembershipPlan::factory()->create([
            'name' => 'a plan',
        ]);
        $updated = $this->repository->update($model, [
            'name' => 'the same plan',
        ]);

        $this->assertEquals('the same plan', $updated->name);
    }

    public function test_update_success_with_cost(): void
    {
        $model = MembershipPlan::factory()->create([
            'name' => 'a plan',
        ]);
        MembershipPlanRate::factory()->create([
            'cost' => 1.99,
            'membership_plan_id' => $model->id,
        ]);
        $updated = $this->repository->update($model, [
            'current_cost' => 3.99,
        ]);

        $this->assertEquals(3.99, $updated->current_cost);
    }

    public function test_update_success_with_features(): void
    {
        $model = MembershipPlan::factory()->create();
        $model->features()->sync(Feature::factory()->count(2)->create()->pluck('id'));

        $updated = $this->repository->update($model, [
            'features' => Feature::factory()->count(3)->create()->pluck('id'),
        ]);

        $this->assertCount(3, $updated->features);
    }

    public function test_delete_success(): void
    {
        $model = MembershipPlan::factory()->create();

        $this->repository->delete($model);

        $this->assertNull(MembershipPlan::find($model->id));
    }

    public function test_find_default_membership_plan_for_entity(): void
    {
        $this->assertNull($this->repository->findDefaultMembershipPlanForEntity('user'));

        MembershipPlan::factory()->create([
            'entity_type' => 'user',
            'default' => 0,
        ]);
        $this->assertNull($this->repository->findDefaultMembershipPlanForEntity('user'));

        MembershipPlan::factory()->create([
            'entity_type' => 'user',
            'default' => 0,
        ]);
        $this->assertNull($this->repository->findDefaultMembershipPlanForEntity('organization'));

        MembershipPlan::factory()->create([
            'entity_type' => 'user',
            'default' => 1,
        ]);
        $this->assertNotNull($this->repository->findDefaultMembershipPlanForEntity('user'));
    }
}
