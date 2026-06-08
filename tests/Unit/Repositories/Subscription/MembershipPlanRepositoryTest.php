<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Repositories\Subscription;

use App\Models\Subscription\MembershipPlan;
use Mockery;
use Polis\Contracts\Repositories\Subscription\MembershipPlanRateRepositoryContract;
use Polis\Models\BaseModelAbstract;
use Polis\Repositories\Subscription\MembershipPlanRepository;
use Polis\Tests\TestCase;

/**
 * Coverage for MembershipPlanRepository — the create/update overrides that
 * spawn (or de-activate) MembershipPlanRate records and the default-plan
 * lookup helper.
 *
 * Mocks satisfy BOTH App\Models\Subscription\MembershipPlan (so the repo
 * constructor accepts them) AND Polis\Models\BaseModelAbstract (so the
 * recursive rate-repo create can accept them as $relatedModel). The
 * pre-assigned `wasRecentlyCreated = true` short-circuits the
 * post-relationship save in BaseRepositoryAbstract::create.
 */
final class MembershipPlanRepositoryTest extends TestCase
{
    private function buildModelMock(int $id = 42)
    {
        $mock = Mockery::mock(MembershipPlan::class);
        // Eloquent intercepts both property reads and writes via
        // __get/__set + setAttribute/getAttribute. Stub the read path to
        // return the model id and accept any write via setAttribute. The
        // wasRecentlyCreated check in BaseRepositoryAbstract::create()
        // reads a public property (not an Eloquent attribute) — to make
        // that read succeed we override its return value via
        // getAttribute as well.
        $mock->shouldReceive('setAttribute');
        $mock->shouldReceive('getAttribute')->andReturn($id);
        // wasRecentlyCreated is a real public field on Eloquent; assigning
        // here goes through setAttribute (which we now accept) but reads
        // bypass __get because the property exists on the parent class.
        $mock->wasRecentlyCreated = true;

        return $mock;
    }

    public function test_create_spawns_active_rate_when_current_cost_supplied(): void
    {
        $modelMock = $this->buildModelMock();
        $modelMock->shouldReceive('newInstance')->once()->with([])->andReturn($modelMock);
        $modelMock->shouldReceive('features->sync')->once()->with([]);

        $rateRepo = Mockery::mock(MembershipPlanRateRepositoryContract::class);
        $rateRepo->shouldReceive('create')
            ->once()
            ->with(['cost' => 19.99, 'active' => true], $modelMock);

        $repo = new MembershipPlanRepository($modelMock, $this->getGenericLogMock(), $rateRepo);
        $repo->create(['current_cost' => 19.99]);
    }

    public function test_create_skips_rate_creation_when_no_current_cost_given(): void
    {
        $modelMock = $this->buildModelMock(7);
        $modelMock->shouldReceive('newInstance')->once()->andReturn($modelMock);
        $modelMock->shouldReceive('features->sync')->once()->with([]);

        $rateRepo = Mockery::mock(MembershipPlanRateRepositoryContract::class);
        $rateRepo->shouldNotReceive('create');

        $repo = new MembershipPlanRepository($modelMock, $this->getGenericLogMock(), $rateRepo);
        $repo->create();
    }

    public function test_create_syncs_features_when_passed(): void
    {
        $modelMock = $this->buildModelMock(11);
        $modelMock->shouldReceive('newInstance')->once()->with([])->andReturn($modelMock);
        $modelMock->shouldReceive('features->sync')->once()->with([1, 2, 3]);

        $rateRepo = Mockery::mock(MembershipPlanRateRepositoryContract::class);
        $rateRepo->shouldNotReceive('create');

        $repo = new MembershipPlanRepository($modelMock, $this->getGenericLogMock(), $rateRepo);
        $repo->create(['features' => [1, 2, 3]]);
    }

    public function test_find_default_membership_plan_for_entity_uses_default_eq_one_filter(): void
    {
        $queryMock = Mockery::mock('Illuminate\\Database\\Eloquent\\Builder');
        $queryMock->shouldReceive('where')->once()->with('entity_type', '=', 'organization')->andReturnSelf();
        $queryMock->shouldReceive('where')->once()->with('default', '=', 1)->andReturnSelf();
        $expected = $this->buildModelMock(99);
        $queryMock->shouldReceive('first')->once()->andReturn($expected);

        $modelMock = $this->buildModelMock();
        $modelMock->shouldReceive('newQuery')->once()->andReturn($queryMock);

        $rateRepo = Mockery::mock(MembershipPlanRateRepositoryContract::class);

        $repo = new MembershipPlanRepository($modelMock, $this->getGenericLogMock(), $rateRepo);
        $result = $repo->findDefaultMembershipPlanForEntity('organization');

        $this->assertSame($expected, $result);
    }
}
