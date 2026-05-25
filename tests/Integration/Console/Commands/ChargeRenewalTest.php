<?php

declare(strict_types=1);

namespace Polis\Tests\Integration\Console\Commands;

use App\Console\Commands\ChargeRenewal;
use App\Models\Payment\PaymentMethod;
use App\Models\Subscription\MembershipPlanRate;
use App\Models\Subscription\Subscription;
use Carbon\Carbon;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Polis\Contracts\Repositories\Messaging\MessageRepositoryContract;
use Polis\Contracts\Services\StripePaymentServiceContract;
use Polis\Repositories\Subscription\MembershipPlanRateRepository;
use Polis\Repositories\Subscription\SubscriptionRepository;
use Polis\Tests\DatabaseSetupTrait;
use Polis\Tests\TestCase;

/**
 * Class ChargeRenewalTest
 */
final class ChargeRenewalTest extends TestCase
{
    use DatabaseSetupTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();
    }

    public function test_handle(): void
    {
        $now = new Carbon('2018-01-21 12:33:23');
        Carbon::setTestNow($now);

        /** @var Subscription $nonRecurringSubscription */
        $nonRecurringSubscription = Subscription::factory()->create([
            'recurring' => false,
            'expires_at' => $now,
        ]);
        /** @var Subscription $stripeSubscription */
        $stripeSubscription = Subscription::factory()->create([
            'recurring' => true,
            'expires_at' => $now,
            'membership_plan_rate_id' => MembershipPlanRate::factory()->create([
                'cost' => 35,
            ])->id,
            'payment_method_id' => PaymentMethod::factory()->create([
                'payment_method_type' => 'stripe',
                'payment_method_key' => 'test_stripe',
            ])->id,
        ]);

        $messageRepository = mock(MessageRepositoryContract::class);
        $paymentService = mock(StripePaymentServiceContract::class);
        $subscriptionRepository = new SubscriptionRepository(
            new Subscription,
            $this->getGenericLogMock(),
            new MembershipPlanRateRepository(
                new MembershipPlanRate,
                $this->getGenericLogMock(),
            )
        );
        $config = mock(Repository::class);
        $config->shouldReceive('get')->once()->with('app.name')->andReturn('Athenia');

        $command = new ChargeRenewal($paymentService, $subscriptionRepository, $messageRepository, $config);

        $paymentService->shouldReceive('createPayment')->once()->with(\Mockery::on(function ($user) use ($stripeSubscription) {
            $this->assertEquals($user->id, $stripeSubscription->subscriber_id);

            return true;
        }), \Mockery::on(function (PaymentMethod $paymentMethod) {
            return true;
        }), 'Subscription renewal for '.$stripeSubscription->membershipPlanRate->membershipPlan->name, [[
            'item_id' => $stripeSubscription->id,
            'item_type' => 'subscription',
            'amount' => 35.0,
        ]]);

        $messageRepository->shouldReceive('sendEmailToUser')->once()->with(
            \Mockery::on(function ($user) use ($stripeSubscription) {
                return $user->id == $stripeSubscription->subscriber_id;
            }),
            'Athenia Membership Successfully Renewed',
            'membership-renewed',
            \Mockery::on(function ($data) use ($stripeSubscription) {

                if (! Arr::has($data, ['membership_name', 'membership_cost', 'expiration_date'])) {
                    return false;
                }

                if (! Str::contains($data['membership_cost'], '35.00')) {
                    return false;
                }
                if (! Str::contains($data['membership_name'], $stripeSubscription->membershipPlanRate->membershipPlan->name)) {
                    return false;
                }
                if (! Str::contains($data['expiration_date'], 'January 21st 2019')) {
                    return false;
                }

                return true;
            })
        );

        $messageRepository->shouldReceive('sendEmailToUser')->once()->with(
            \Mockery::on(function ($user) use ($nonRecurringSubscription) {
                return $user->id == $nonRecurringSubscription->subscriber_id;
            }),
            'Athenia Membership Expired',
            'membership-expired',
            \Mockery::on(function ($data) {

                if (! Arr::has($data, ['membership_name'])) {
                    return false;
                }

                return true;
            })
        );

        $command->handle();
    }
}
