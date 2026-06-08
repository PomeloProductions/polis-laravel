<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Repositories\Subscription;

use App\Models\Subscription\MembershipPlan;
use App\Models\Subscription\MembershipPlanRate;
use App\Models\Subscription\Subscription;
use Carbon\Carbon;
use Mockery;
use Polis\Contracts\Repositories\Subscription\MembershipPlanRateRepositoryContract;
use Polis\Repositories\Subscription\SubscriptionRepository;
use Polis\Tests\TestCase;

/**
 * Coverage for SubscriptionRepository — the create() override that wires
 * subscription metadata (subscribed_at, last_renewed_at, expires_at) and
 * the two date-window query helpers (findExpiring + findExpiresAfter).
 *
 * Carbon::setTestNow() pins "now" so the expires_at assertions are exact.
 *
 * For the create() tests we construct lightweight concrete instances of
 * the MembershipPlan/MembershipPlanRate fixtures (both extend
 * BaseModelAbstract) instead of Mockery doubles — Eloquent's __set/__get
 * via setAttribute/getAttribute would otherwise force every property
 * read/write to be explicitly stubbed, which adds noise without testing
 * anything new. Real model instances let us set fields the natural way.
 */
final class SubscriptionRepositoryTest extends TestCase
{
    private function buildSubscriptionMock(int $id = 1)
    {
        $mock = Mockery::mock(Subscription::class);
        $mock->shouldReceive('setAttribute');
        $mock->shouldReceive('getAttribute')->andReturn($id);
        $mock->wasRecentlyCreated = true;

        return $mock;
    }

    private function buildRate(int $trialPeriod, string $duration): MembershipPlanRate
    {
        $plan = new MembershipPlan;
        $plan->forceFill([
            'trial_period' => $trialPeriod,
            'duration' => $duration,
        ]);

        $rate = new MembershipPlanRate;
        $rate->setRelation('membershipPlan', $plan);

        return $rate;
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_create_for_trial_with_trial_period_uses_add_days(): void
    {
        Carbon::setTestNow('2026-01-01 00:00:00');

        $rate = $this->buildRate(14, MembershipPlan::DURATION_MONTH);
        $rateRepo = Mockery::mock(MembershipPlanRateRepositoryContract::class);
        $rateRepo->shouldReceive('findOrFail')->once()->with(5)->andReturn($rate);

        $modelMock = $this->buildSubscriptionMock();
        $modelMock->shouldReceive('newInstance')
            ->once()
            ->andReturnUsing(function ($data) use ($modelMock) {
                $this->assertEquals('2026-01-15 00:00:00', $data['expires_at']->format('Y-m-d H:i:s'));
                $this->assertTrue($data['is_trial']);

                return $modelMock;
            });

        $repo = new SubscriptionRepository($modelMock, $this->getGenericLogMock(), $rateRepo);
        $repo->create(['membership_plan_rate_id' => 5, 'is_trial' => true]);
    }

    public function test_create_when_not_trial_and_month_duration_adds_one_month(): void
    {
        Carbon::setTestNow('2026-01-01 00:00:00');

        $rate = $this->buildRate(7, MembershipPlan::DURATION_MONTH);
        $rateRepo = Mockery::mock(MembershipPlanRateRepositoryContract::class);
        $rateRepo->shouldReceive('findOrFail')->once()->andReturn($rate);

        $modelMock = $this->buildSubscriptionMock();
        $modelMock->shouldReceive('newInstance')
            ->once()
            ->andReturnUsing(function ($data) use ($modelMock) {
                $this->assertFalse($data['is_trial']);
                $this->assertEquals('2026-02-01 00:00:00', $data['expires_at']->format('Y-m-d H:i:s'));

                return $modelMock;
            });

        $repo = new SubscriptionRepository($modelMock, $this->getGenericLogMock(), $rateRepo);
        $repo->create(['membership_plan_rate_id' => 5, 'is_trial' => false]);
    }

    public function test_create_when_not_trial_and_year_duration_adds_one_year(): void
    {
        Carbon::setTestNow('2026-01-01 00:00:00');

        $rate = $this->buildRate(0, MembershipPlan::DURATION_YEAR);
        $rateRepo = Mockery::mock(MembershipPlanRateRepositoryContract::class);
        $rateRepo->shouldReceive('findOrFail')->once()->andReturn($rate);

        $modelMock = $this->buildSubscriptionMock();
        $modelMock->shouldReceive('newInstance')
            ->once()
            ->andReturnUsing(function ($data) use ($modelMock) {
                $this->assertEquals('2027-01-01 00:00:00', $data['expires_at']->format('Y-m-d H:i:s'));

                return $modelMock;
            });

        $repo = new SubscriptionRepository($modelMock, $this->getGenericLogMock(), $rateRepo);
        $repo->create(['membership_plan_rate_id' => 5]);
    }

    public function test_create_with_trial_but_no_trial_period_falls_back_to_duration_branch(): void
    {
        Carbon::setTestNow('2026-01-01 00:00:00');

        $rate = $this->buildRate(0, MembershipPlan::DURATION_MONTH); // trial_period=0 -> falsy
        $rateRepo = Mockery::mock(MembershipPlanRateRepositoryContract::class);
        $rateRepo->shouldReceive('findOrFail')->once()->andReturn($rate);

        $modelMock = $this->buildSubscriptionMock();
        $modelMock->shouldReceive('newInstance')
            ->once()
            ->andReturnUsing(function ($data) use ($modelMock) {
                $this->assertFalse($data['is_trial']);
                $this->assertEquals('2026-02-01 00:00:00', $data['expires_at']->format('Y-m-d H:i:s'));

                return $modelMock;
            });

        $repo = new SubscriptionRepository($modelMock, $this->getGenericLogMock(), $rateRepo);
        $repo->create(['membership_plan_rate_id' => 5, 'is_trial' => true]);
    }

    public function test_find_expiring_uses_inclusive_window(): void
    {
        $query = Mockery::mock('Illuminate\\Database\\Eloquent\\Builder');
        $query->shouldReceive('where')->ordered()->once()
            ->with('expires_at', '>=', Mockery::on(fn ($v) => $v->format('Y-m-d H:i:s') === '2026-06-01 00:00:00'))
            ->andReturnSelf();
        $query->shouldReceive('where')->ordered()->once()
            ->with('expires_at', '<=', Mockery::on(fn ($v) => $v->format('Y-m-d H:i:s') === '2026-06-01 23:59:59'))
            ->andReturnSelf();
        $query->shouldReceive('get')->once()->andReturn(new \Illuminate\Database\Eloquent\Collection);

        $modelMock = $this->buildSubscriptionMock();
        $modelMock->shouldReceive('newQuery')->once()->andReturn($query);

        $rateRepo = Mockery::mock(MembershipPlanRateRepositoryContract::class);

        $repo = new SubscriptionRepository($modelMock, $this->getGenericLogMock(), $rateRepo);
        $repo->findExpiring(Carbon::parse('2026-06-01 12:30:00'));
    }

    public function test_find_expires_after_without_type_does_not_apply_subscriber_filter(): void
    {
        $query = Mockery::mock('Illuminate\\Database\\Eloquent\\Builder');
        $query->shouldReceive('whereNull')->once()->with('expires_at')->andReturnSelf();
        $query->shouldReceive('orWhere')->once()->andReturnSelf();
        $query->shouldReceive('get')->once()->andReturn(new \Illuminate\Database\Eloquent\Collection);
        $query->shouldNotReceive('where');

        $modelMock = $this->buildSubscriptionMock();
        $modelMock->shouldReceive('newQuery')->once()->andReturn($query);

        $rateRepo = Mockery::mock(MembershipPlanRateRepositoryContract::class);

        $repo = new SubscriptionRepository($modelMock, $this->getGenericLogMock(), $rateRepo);
        $repo->findExpiresAfter(Carbon::parse('2026-06-01'));
    }

    public function test_find_expires_after_with_type_applies_subscriber_filter(): void
    {
        $query = Mockery::mock('Illuminate\\Database\\Eloquent\\Builder');
        $query->shouldReceive('whereNull')->once()->with('expires_at')->andReturnSelf();
        $query->shouldReceive('orWhere')->once()->andReturnSelf();
        $query->shouldReceive('where')->once()->with('subscriber_type', '=', 'organization')->andReturnSelf();
        $query->shouldReceive('get')->once()->andReturn(new \Illuminate\Database\Eloquent\Collection);

        $modelMock = $this->buildSubscriptionMock();
        $modelMock->shouldReceive('newQuery')->once()->andReturn($query);

        $rateRepo = Mockery::mock(MembershipPlanRateRepositoryContract::class);

        $repo = new SubscriptionRepository($modelMock, $this->getGenericLogMock(), $rateRepo);
        $repo->findExpiresAfter(Carbon::parse('2026-06-01'), 'organization');
    }
}
