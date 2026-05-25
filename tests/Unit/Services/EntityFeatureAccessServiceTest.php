<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Services;

use App\Models\Feature;
use App\Models\Subscription\MembershipPlan;
use App\Models\Subscription\MembershipPlanRate;
use App\Models\Subscription\Subscription;
use App\Models\User\User;
use Carbon\Carbon;
use Polis\Contracts\Repositories\Subscription\MembershipPlanRepositoryContract;
use Polis\Services\EntityFeatureAccessService;
use Polis\Tests\CustomMockInterface;
use Polis\Tests\TestCase;

/**
 * Class EntityFeatureAccessServiceTest
 */
final class EntityFeatureAccessServiceTest extends TestCase
{
    /**
     * @var MembershipPlanRepositoryContract|CustomMockInterface
     */
    private $membershipPlanRepository;

    private EntityFeatureAccessService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->membershipPlanRepository = mock(MembershipPlanRepositoryContract::class);
        $this->service = new EntityFeatureAccessService($this->membershipPlanRepository);
    }

    public function test_can_access_returns_false_without_default_membership_plan(): void
    {
        $user = new User([
            'subscriptions' => collect([]),
        ]);

        $this->membershipPlanRepository
            ->shouldReceive('findDefaultMembershipPlanForEntity')
            ->once()->with('user')->andReturnNull();

        $this->assertFalse($this->service->canAccess($user, 21));
    }

    public function test_can_access_returns_false_when_default_membership_plan_does_not_contain_feature(): void
    {
        $feature = new Feature;
        $feature->id = 12;

        $membershipPlan = new MembershipPlan([
            'features' => collect([
                $feature,
            ]),
        ]);

        $user = new User([
            'subscriptions' => collect([]),
        ]);

        $this->membershipPlanRepository
            ->shouldReceive('findDefaultMembershipPlanForEntity')
            ->once()->with('user')->andReturn($membershipPlan);

        $this->assertFalse($this->service->canAccess($user, 21));
    }

    public function test_can_access_returns_true_when_default_membership_plan_does_contains_feature(): void
    {
        $feature = new Feature;
        $feature->id = 21;

        $membershipPlan = new MembershipPlan([
            'features' => collect([
                $feature,
            ]),
        ]);

        $user = new User([
            'subscriptions' => collect([]),
        ]);

        $this->membershipPlanRepository
            ->shouldReceive('findDefaultMembershipPlanForEntity')
            ->once()->with('user')->andReturn($membershipPlan);

        $this->assertTrue($this->service->canAccess($user, 21));
    }

    public function test_can_access_returns_false_when_entity_membership_plan_does_not_contain_feature(): void
    {
        $feature = new Feature;
        $feature->id = 12;

        $membershipPlan = new MembershipPlan([
            'features' => collect([
                $feature,
            ]),
        ]);

        $user = new User([
            'subscriptions' => collect([
                new Subscription([
                    'expires_at' => Carbon::now()->addYear(),
                    'membershipPlanRate' => new MembershipPlanRate([
                        'membershipPlan' => $membershipPlan,
                    ]),
                ]),
            ]),
        ]);

        $this->assertFalse($this->service->canAccess($user, 21));
    }

    public function test_can_access_returns_true_when_enity_membership_plan_does_contains_feature(): void
    {
        $feature = new Feature;
        $feature->id = 21;

        $membershipPlan = new MembershipPlan([
            'features' => collect([
                $feature,
            ]),
        ]);

        $user = new User([
            'subscriptions' => collect([
                new Subscription([
                    'expires_at' => Carbon::now()->addYear(),
                    'membershipPlanRate' => new MembershipPlanRate([
                        'membershipPlan' => $membershipPlan,
                    ]),
                ]),
            ]),
        ]);

        $this->assertTrue($this->service->canAccess($user, 21));
    }
}
