<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Repositories\Subscription;

use App\Models\Subscription\MembershipPlan;
use Mockery;
use Polis\Contracts\Repositories\Subscription\MembershipPlanRateRepositoryContract;
use Polis\Repositories\Subscription\MembershipPlanRepository;
use Polis\Tests\TestCase;

/**
 * Coverage for MembershipPlanRepository::update — the rate-deactivation +
 * new-rate-creation flow that fires when `current_cost` changes, plus the
 * feature-sync side-effect.
 *
 * To exercise the branch that reads $model->current_cost and
 * $model->membershipPlanRates, we route attribute reads through a per-key
 * resolver instead of a one-size-fits-all `andReturn`. This keeps the
 * mocks faithful to how Eloquent dispatches __get to getAttribute.
 */
final class MembershipPlanUpdateTest extends TestCase
{
    private function buildModelMock(int $id = 42, ?float $currentCost = null, array $existingRates = [])
    {
        $mock = Mockery::mock(MembershipPlan::class);
        $mock->shouldReceive('setAttribute');
        $attributes = [
            'id' => $id,
            'current_cost' => $currentCost,
            'membershipPlanRates' => $existingRates,
        ];
        $mock->shouldReceive('getAttribute')->andReturnUsing(function ($key) use ($attributes) {
            return $attributes[$key] ?? null;
        });
        $mock->wasRecentlyCreated = true;

        return $mock;
    }

    public function test_update_with_changed_cost_deactivates_old_rates_and_creates_new_one(): void
    {
        $rateA = Mockery::mock(\Polis\Models\BaseModelAbstract::class);
        $rateB = Mockery::mock(\Polis\Models\BaseModelAbstract::class);
        $modelMock = $this->buildModelMock(7, currentCost: 10.0, existingRates: [$rateA, $rateB]);
        $modelMock->shouldReceive('update')->once()->andReturn(true);
        $modelMock->shouldReceive('unsetRelations')->once();

        $rateRepo = Mockery::mock(MembershipPlanRateRepositoryContract::class);
        $rateRepo->shouldReceive('update')->once()->with($rateA, ['active' => false]);
        $rateRepo->shouldReceive('update')->once()->with($rateB, ['active' => false]);
        $rateRepo->shouldReceive('create')
            ->once()
            ->with(['cost' => 25.0, 'active' => true], $modelMock);

        $repo = new MembershipPlanRepository($modelMock, $this->getGenericLogMock(), $rateRepo);
        $result = $repo->update($modelMock, ['current_cost' => 25.0]);

        $this->assertSame($modelMock, $result);
    }

    public function test_update_with_same_cost_does_not_recreate_rate(): void
    {
        $modelMock = $this->buildModelMock(7, currentCost: 10.0);
        $modelMock->shouldReceive('update')->once()->andReturn(true);

        $rateRepo = Mockery::mock(MembershipPlanRateRepositoryContract::class);
        $rateRepo->shouldNotReceive('create');

        $repo = new MembershipPlanRepository($modelMock, $this->getGenericLogMock(), $rateRepo);
        $repo->update($modelMock, ['current_cost' => 10.0]);
    }

    public function test_update_with_no_current_cost_does_not_touch_rates(): void
    {
        $modelMock = $this->buildModelMock(7);
        $modelMock->shouldReceive('update')->once()->andReturn(true);

        $rateRepo = Mockery::mock(MembershipPlanRateRepositoryContract::class);
        $rateRepo->shouldNotReceive('create');
        $rateRepo->shouldNotReceive('update');

        $repo = new MembershipPlanRepository($modelMock, $this->getGenericLogMock(), $rateRepo);
        $repo->update($modelMock, ['some_other_field' => 'value']);
    }

    public function test_update_syncs_features_when_provided(): void
    {
        $modelMock = $this->buildModelMock(7);
        $modelMock->shouldReceive('update')->once()->andReturn(true);
        $modelMock->shouldReceive('features->sync')->once()->with([5, 6]);

        $rateRepo = Mockery::mock(MembershipPlanRateRepositoryContract::class);

        $repo = new MembershipPlanRepository($modelMock, $this->getGenericLogMock(), $rateRepo);
        $repo->update($modelMock, ['features' => [5, 6]]);
    }
}
