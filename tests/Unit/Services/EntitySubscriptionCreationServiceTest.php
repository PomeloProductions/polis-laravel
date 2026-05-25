<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Services;

use App\Models\Payment\PaymentMethod;
use App\Models\Subscription\MembershipPlan;
use App\Models\Subscription\MembershipPlanRate;
use App\Models\Subscription\Subscription;
use App\Models\User\User;
use Carbon\Carbon;
use Polis\Contracts\Repositories\Subscription\SubscriptionRepositoryContract;
use Polis\Contracts\Services\ProratingCalculationServiceContract;
use Polis\Contracts\Services\StripePaymentServiceContract;
use Polis\Services\EntitySubscriptionCreationService;
use Polis\Services\ProratingCalculationService;
use Polis\Tests\CustomMockInterface;
use Polis\Tests\TestCase;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

/**
 * Class EntitySubscriptionCreationServiceTest
 */
final class EntitySubscriptionCreationServiceTest extends TestCase
{
    private ProratingCalculationServiceContract $proratingCalculationService;

    /**
     * @var SubscriptionRepositoryContract|CustomMockInterface
     */
    private $subscriptionRepository;

    /**
     * @var StripePaymentServiceContract|CustomMockInterface
     */
    private $stripePaymentService;

    private EntitySubscriptionCreationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->proratingCalculationService = new ProratingCalculationService;
        $this->subscriptionRepository = mock(SubscriptionRepositoryContract::class);
        $this->stripePaymentService = mock(StripePaymentServiceContract::class);

        $this->service = new EntitySubscriptionCreationService(
            $this->proratingCalculationService,
            $this->subscriptionRepository,
            $this->stripePaymentService
        );
    }

    public function test_throws_exception_when_stripe_fails(): void
    {
        $user = new User([
            'subscriptions' => collect([]),
        ]);
        $user->id = 2354;

        $data = [
            'membership_plan_rate_id' => 234,
        ];

        $newSubscription = new Subscription([
            'membershipPlanRate' => new MembershipPlanRate([
                'cost' => 24,
            ]),
        ]);

        $this->subscriptionRepository->shouldReceive('create')->andReturn($newSubscription);

        $this->stripePaymentService->shouldReceive('createPayment')->andThrow(new \Exception);

        $this->subscriptionRepository->shouldReceive('delete')->with($newSubscription);

        $this->expectException(ServiceUnavailableHttpException::class);

        $this->service->createSubscription($user, $data);
    }

    public function test_successful_without_existing_subscription(): void
    {
        $user = new User([
            'subscriptions' => collect([]),
        ]);
        $user->id = 2354;

        $data = [
            'membership_plan_rate_id' => 234,
        ];

        $newSubscription = new Subscription([
            'membershipPlanRate' => new MembershipPlanRate([
                'cost' => 24,
                'membershipPlan' => new MembershipPlan([
                    'name' => 'A membership',
                ]),
            ]),
            'paymentMethod' => new PaymentMethod,
        ]);

        $this->subscriptionRepository->shouldReceive('create')->andReturn($newSubscription);

        $this->stripePaymentService->shouldReceive('createPayment');

        $result = $this->service->createSubscription($user, $data);

        $this->assertEquals($result, $newSubscription);
    }

    public function test_successful_with_existing_subscription(): void
    {
        $oldSubscription = new Subscription([
            'expires_at' => Carbon::now()->addMonth(),
            'membershipPlanRate' => new MembershipPlanRate([
                'membershipPlan' => new MembershipPlan([
                    'duration' => MembershipPlan::DURATION_MONTH,
                ]),
            ]),
        ]);

        $user = new User([
            'subscriptions' => collect([
                $oldSubscription,
            ]),
        ]);
        $user->id = 2354;

        $testCarbon = Carbon::now();
        Carbon::setTestNow($testCarbon);

        $data = [
            'membership_plan_rate_id' => 234,
        ];

        $newSubscription = new Subscription([
            'membershipPlanRate' => new MembershipPlanRate([
                'cost' => 24,
                'membershipPlan' => new MembershipPlan([
                    'name' => 'A membership',
                ]),
            ]),
            'paymentMethod' => new PaymentMethod,
        ]);

        $this->subscriptionRepository->shouldReceive('create')->andReturn($newSubscription);

        $this->stripePaymentService->shouldReceive('createPayment');

        $this->subscriptionRepository->shouldReceive('update')->once()->with($oldSubscription, [
            'canceled_at' => $testCarbon,
        ]);

        $result = $this->service->createSubscription($user, $data);

        $this->assertEquals($result, $newSubscription);
    }

    public function test_successful_with_new_lifetime_subscription(): void
    {
        $oldSubscription = new Subscription([
            'expires_at' => Carbon::now()->addMonth(),
            'membershipPlanRate' => new MembershipPlanRate([
                'membershipPlan' => new MembershipPlan([
                    'duration' => MembershipPlan::DURATION_MONTH,
                ]),
            ]),
        ]);

        $user = new User([
            'subscriptions' => collect([
                $oldSubscription,
            ]),
        ]);
        $user->id = 2354;

        $testCarbon = Carbon::now();
        Carbon::setTestNow($testCarbon);

        $data = [
            'membership_plan_rate_id' => 234,
        ];

        $newSubscription = new Subscription([
            'membershipPlanRate' => new MembershipPlanRate([
                'cost' => 24,
                'membershipPlan' => new MembershipPlan([
                    'name' => 'A membership',
                    'duration' => MembershipPlan::DURATION_LIFETIME,
                ]),
            ]),
            'paymentMethod' => new PaymentMethod,
        ]);

        $this->subscriptionRepository->shouldReceive('create')->with([
            'membership_plan_rate_id' => 234,
            'subscriber_id' => 2354,
            'subscriber_type' => 'user',
        ])->andReturn($newSubscription);

        $this->stripePaymentService->shouldReceive('createPayment');

        $this->subscriptionRepository->shouldReceive('update')->once()->with($oldSubscription, [
            'canceled_at' => $testCarbon,
        ]);

        $result = $this->service->createSubscription($user, $data);

        $this->assertEquals($result, $newSubscription);
    }
}
