<?php

declare(strict_types=1);

namespace Polis\Tests\Integration\Repositories\Subscription;

use App\Models\Payment\PaymentMethod;
use App\Models\Subscription\MembershipPlan;
use App\Models\Subscription\MembershipPlanRate;
use App\Models\Subscription\Subscription;
use App\Models\User\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Polis\Repositories\Subscription\MembershipPlanRateRepository;
use Polis\Repositories\Subscription\SubscriptionRepository;
use Polis\Tests\DatabaseSetupTrait;
use Polis\Tests\TestCase;
use Polis\Tests\Traits\MocksApplicationLog;

/**
 * Class SubscriptionRepositoryTest
 */
final class SubscriptionRepositoryTest extends TestCase
{
    use DatabaseSetupTrait, MocksApplicationLog;

    /**
     * @var SubscriptionRepository
     */
    protected $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();

        $this->repository = new SubscriptionRepository(
            new Subscription,
            $this->getGenericLogMock(),
            new MembershipPlanRateRepository(
                new MembershipPlanRate,
                $this->getGenericLogMock(),
            )
        );
    }

    public function test_find_all_success(): void
    {
        Subscription::factory()->count(5)->create();
        $items = $this->repository->findAll();
        $this->assertCount(5, $items);
    }

    public function test_find_all_empty(): void
    {
        $items = $this->repository->findAll();
        $this->assertEmpty($items);
    }

    public function test_find_or_fail_success(): void
    {
        $model = Subscription::factory()->create();

        $foundModel = $this->repository->findOrFail($model->id);
        $this->assertEquals($model->id, $foundModel->id);
    }

    public function test_find_or_fail_fails(): void
    {
        Subscription::factory()->create(['id' => 19]);

        $this->expectException(ModelNotFoundException::class);
        $this->repository->findOrFail(20);
    }

    public function test_create_success_with_life_time_membership(): void
    {
        $membershipPlanRate = MembershipPlanRate::factory()->create([
            'membership_plan_id' => MembershipPlan::factory()->create([
                'duration' => MembershipPlan::DURATION_LIFETIME,
            ])->id,
        ]);
        $paymentMethod = PaymentMethod::factory()->create();
        $user = User::factory()->create();

        /** @var Subscription $subscription */
        $subscription = $this->repository->create([
            'payment_method_id' => $paymentMethod->id,
            'membership_plan_rate_id' => $membershipPlanRate->id,
            'subscriber_id' => $user->id,
            'subscriber_type' => 'user',
        ]);

        $this->assertEquals($paymentMethod->id, $subscription->payment_method_id);
        $this->assertEquals($membershipPlanRate->id, $subscription->membership_plan_rate_id);
        $this->assertEquals($user->id, $subscription->subscriber_id);
        $this->assertNull($subscription->expires_at);
    }

    public function test_create_success_with_yearly_membership(): void
    {
        $membershipPlanRate = MembershipPlanRate::factory()->create([
            'membership_plan_id' => MembershipPlan::factory()->create([
                'duration' => MembershipPlan::DURATION_YEAR,
            ])->id,
        ]);
        $paymentMethod = PaymentMethod::factory()->create();
        $user = User::factory()->create();

        Carbon::setTestNow('2018-02-12 00:00:00');

        /** @var Subscription $subscription */
        $subscription = $this->repository->create([
            'payment_method_id' => $paymentMethod->id,
            'membership_plan_rate_id' => $membershipPlanRate->id,
            'subscriber_id' => $user->id,
            'subscriber_type' => 'user',
        ]);

        $this->assertEquals($paymentMethod->id, $subscription->payment_method_id);
        $this->assertEquals($membershipPlanRate->id, $subscription->membership_plan_rate_id);
        $this->assertEquals($user->id, $subscription->subscriber_id);
        $this->assertEquals('2019-02-12 00:00:00', $subscription->expires_at->toDateTimeString());
    }

    public function test_create_success_with_monthly_membership(): void
    {
        $membershipPlanRate = MembershipPlanRate::factory()->create([
            'membership_plan_id' => MembershipPlan::factory()->create([
                'duration' => MembershipPlan::DURATION_MONTH,
            ])->id,
        ]);
        $paymentMethod = PaymentMethod::factory()->create();
        $user = User::factory()->create();

        Carbon::setTestNow('2018-02-12 00:00:00');

        /** @var Subscription $subscription */
        $subscription = $this->repository->create([
            'payment_method_id' => $paymentMethod->id,
            'membership_plan_rate_id' => $membershipPlanRate->id,
            'subscriber_id' => $user->id,
            'subscriber_type' => 'user',
        ]);

        $this->assertEquals($paymentMethod->id, $subscription->payment_method_id);
        $this->assertEquals($membershipPlanRate->id, $subscription->membership_plan_rate_id);
        $this->assertEquals($user->id, $subscription->subscriber_id);
        $this->assertEquals('2018-03-12 00:00:00', $subscription->expires_at->toDateTimeString());
    }

    public function test_create_success_when_attempting_a_trial_without_a_trial_period(): void
    {
        $membershipPlanRate = MembershipPlanRate::factory()->create();
        $paymentMethod = PaymentMethod::factory()->create();
        $user = User::factory()->create();

        Carbon::setTestNow('2018-02-12 00:00:00');

        /** @var Subscription $subscription */
        $subscription = $this->repository->create([
            'payment_method_id' => $paymentMethod->id,
            'membership_plan_rate_id' => $membershipPlanRate->id,
            'subscriber_id' => $user->id,
            'subscriber_type' => 'user',
            'is_trial' => true,
        ]);

        $this->assertEquals($paymentMethod->id, $subscription->payment_method_id);
        $this->assertEquals($membershipPlanRate->id, $subscription->membership_plan_rate_id);
        $this->assertEquals($user->id, $subscription->subscriber_id);
        $this->assertFalse($subscription->is_trial);
    }

    public function test_create_success_with_trial_period(): void
    {
        $membershipPlanRate = MembershipPlanRate::factory()->create([
            'membership_plan_id' => MembershipPlan::factory()->create([
                'trial_period' => 14,
            ])->id,
        ]);
        $paymentMethod = PaymentMethod::factory()->create();
        $user = User::factory()->create();

        Carbon::setTestNow('2018-02-12 00:00:00');

        /** @var Subscription $subscription */
        $subscription = $this->repository->create([
            'payment_method_id' => $paymentMethod->id,
            'membership_plan_rate_id' => $membershipPlanRate->id,
            'subscriber_id' => $user->id,
            'subscriber_type' => 'user',
            'is_trial' => true,
        ]);

        $this->assertEquals($paymentMethod->id, $subscription->payment_method_id);
        $this->assertEquals($membershipPlanRate->id, $subscription->membership_plan_rate_id);
        $this->assertEquals($user->id, $subscription->subscriber_id);
        $this->assertTrue($subscription->is_trial);
        $this->assertEquals('2018-02-26 00:00:00', $subscription->expires_at->toDateTimeString());
    }

    public function test_update_success(): void
    {
        $model = Subscription::factory()->create([
            'expires_at' => null,
        ]);
        $this->repository->update($model, [
            'expires_at' => Carbon::now(),
        ]);

        /** @var Subscription $updated */
        $updated = Subscription::find($model->id);
        $this->assertNotNull($updated->expires_at);
    }

    public function test_delete_success(): void
    {
        $model = Subscription::factory()->create();

        $this->repository->delete($model);

        $this->assertNull(Subscription::find($model->id));
    }

    public function test_find_expiring(): void
    {
        $expirationDate = new Carbon('2018-10-21 04:00:00');

        $subscription1 = Subscription::factory()->create([
            'expires_at' => '2018-10-21 07:10:00',
        ]);
        $subscription2 = Subscription::factory()->create([
            'expires_at' => '2018-10-21 22:10:44',
        ]);
        $subscription3 = Subscription::factory()->create([
            'expires_at' => '2018-10-21 23:59:59',
        ]);
        $subscription4 = Subscription::factory()->create([
            'expires_at' => '2018-10-21 00:00:00',
        ]);
        $subscription5 = Subscription::factory()->create([
            'expires_at' => '2018-10-22 00:00:00',
        ]);
        $subscription6 = Subscription::factory()->create([
            'expires_at' => '2018-10-20 23:59:59',
        ]);
        $subscription7 = Subscription::factory()->create([
            'expires_at' => '2019-04-12 12:40:23',
        ]);

        $result = $this->repository->findExpiring($expirationDate);

        $this->assertCount(4, $result);
        $this->assertContains($subscription1->id, $result->pluck('id'));
        $this->assertContains($subscription2->id, $result->pluck('id'));
        $this->assertContains($subscription3->id, $result->pluck('id'));
        $this->assertContains($subscription4->id, $result->pluck('id'));
        $this->assertNotContains($subscription5->id, $result->pluck('id'));
        $this->assertNotContains($subscription6->id, $result->pluck('id'));
        $this->assertNotContains($subscription7->id, $result->pluck('id'));
    }

    public function test_find_expires_after(): void
    {
        $expirationDate = new Carbon('2018-10-21 04:00:00');

        $subscription1 = Subscription::factory()->create([
            'expires_at' => '2018-10-19 07:10:00',
        ]);
        $subscription2 = Subscription::factory()->create([
            'expires_at' => '2018-10-21 22:10:44',
        ]);
        $subscription3 = Subscription::factory()->create([
            'expires_at' => '2018-10-25 23:59:59',
        ]);
        $subscription4 = Subscription::factory()->create([
            'expires_at' => '2017-10-21 00:00:00',
        ]);
        $subscription5 = Subscription::factory()->create([
            'expires_at' => '2018-10-22 00:00:00',
        ]);
        $subscription6 = Subscription::factory()->create([
            'expires_at' => null,
        ]);
        $subscription7 = Subscription::factory()->create([
            'expires_at' => '2019-04-12 12:40:23',
            'subscriber_type' => 'organization',
        ]);

        $result = $this->repository->findExpiresAfter($expirationDate, 'user');

        $this->assertCount(4, $result);
        $this->assertContains($subscription2->id, $result->pluck('id'));
        $this->assertContains($subscription3->id, $result->pluck('id'));
        $this->assertContains($subscription5->id, $result->pluck('id'));
        $this->assertContains($subscription6->id, $result->pluck('id'));
        $this->assertNotContains($subscription1->id, $result->pluck('id'));
        $this->assertNotContains($subscription4->id, $result->pluck('id'));
        $this->assertNotContains($subscription7->id, $result->pluck('id'));

        $result = $this->repository->findExpiresAfter($expirationDate);

        $this->assertCount(5, $result);
        $this->assertContains($subscription2->id, $result->pluck('id'));
        $this->assertContains($subscription3->id, $result->pluck('id'));
        $this->assertContains($subscription5->id, $result->pluck('id'));
        $this->assertContains($subscription6->id, $result->pluck('id'));
        $this->assertContains($subscription7->id, $result->pluck('id'));
        $this->assertNotContains($subscription1->id, $result->pluck('id'));
        $this->assertNotContains($subscription4->id, $result->pluck('id'));
    }
}
