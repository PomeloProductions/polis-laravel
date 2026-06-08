<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Policies;

use App\Models\Role;
use Mockery;
use Polis\Contracts\Models\IsAnEntityContract;
use Polis\Tests\Fixtures\Models\MembershipPlan;
use Polis\Tests\Fixtures\Models\Subscription;
use Polis\Tests\Fixtures\Policies\Subscription\MembershipPlanPolicy;
use Polis\Tests\Fixtures\Policies\Subscription\MembershipPlanRatePolicy;
use Polis\Tests\Fixtures\Policies\Subscription\SubscriptionPolicy;
use Polis\Tests\TestCase;

/**
 * Coverage for the Subscription-namespaced policy abstracts.
 *
 * - MembershipPlanPolicy: anonymous read, owner-only create/update/delete.
 * - MembershipPlanRatePolicy: all() returns false (super-admin only via before()).
 * - SubscriptionPolicy: ADMINISTRATOR + owner-id boundary check on update.
 */
final class SubscriptionPolicyAbstractTest extends TestCase
{
    public function test_membership_plan_policy_all_returns_true(): void
    {
        $policy = new MembershipPlanPolicy;
        $this->assertTrue($policy->all(Mockery::mock('App\\Models\\User\\User')));
    }

    public function test_membership_plan_policy_view_returns_true(): void
    {
        $policy = new MembershipPlanPolicy;
        $plan = new MembershipPlan;
        $this->assertTrue($policy->view(Mockery::mock('App\\Models\\User\\User'), $plan));
    }

    public function test_membership_plan_policy_create_returns_false(): void
    {
        $policy = new MembershipPlanPolicy;
        $this->assertFalse($policy->create(Mockery::mock('App\\Models\\User\\User')));
    }

    public function test_membership_plan_policy_update_returns_false(): void
    {
        $policy = new MembershipPlanPolicy;
        $plan = new MembershipPlan;
        $this->assertFalse($policy->update(Mockery::mock('App\\Models\\User\\User'), $plan));
    }

    public function test_membership_plan_policy_delete_returns_false(): void
    {
        $policy = new MembershipPlanPolicy;
        $plan = new MembershipPlan;
        $this->assertFalse($policy->delete(Mockery::mock('App\\Models\\User\\User'), $plan));
    }

    public function test_membership_plan_rate_policy_all_returns_false(): void
    {
        $policy = new MembershipPlanRatePolicy;
        $this->assertFalse($policy->all(Mockery::mock('App\\Models\\User\\User')));
    }

    public function test_subscription_policy_all_requires_administrator(): void
    {
        $policy = new SubscriptionPolicy;
        $user = Mockery::mock('App\\Models\\User\\User');
        $entity = Mockery::mock(IsAnEntityContract::class);
        $entity->shouldReceive('canUserManageEntity')->once()->with($user, Role::ADMINISTRATOR)->andReturn(true);

        $this->assertTrue($policy->all($user, $entity));
    }

    public function test_subscription_policy_create_requires_administrator(): void
    {
        $policy = new SubscriptionPolicy;
        $user = Mockery::mock('App\\Models\\User\\User');
        $entity = Mockery::mock(IsAnEntityContract::class);
        $entity->shouldReceive('canUserManageEntity')->once()->with($user, Role::ADMINISTRATOR)->andReturn(false);

        $this->assertFalse($policy->create($user, $entity));
    }

    public function test_subscription_policy_update_allows_admin_owner(): void
    {
        $policy = new SubscriptionPolicy;
        $user = Mockery::mock('App\\Models\\User\\User');
        $entity = Mockery::mock(IsAnEntityContract::class);
        $entity->id = 5;
        $entity->shouldReceive('canUserManageEntity')->once()->with($user, Role::ADMINISTRATOR)->andReturn(true);
        $entity->shouldReceive('morphRelationName')->andReturn('user');

        $subscription = new Subscription;
        $subscription->subscriber_type = 'user';
        $subscription->subscriber_id = 5;

        $this->assertTrue($policy->update($user, $entity, $subscription));
    }

    public function test_subscription_policy_update_denies_when_subscriber_type_mismatches(): void
    {
        $policy = new SubscriptionPolicy;
        $user = Mockery::mock('App\\Models\\User\\User');
        $entity = Mockery::mock(IsAnEntityContract::class);
        $entity->id = 5;
        $entity->shouldReceive('canUserManageEntity')->once()->with($user, Role::ADMINISTRATOR)->andReturn(true);
        $entity->shouldReceive('morphRelationName')->andReturn('user');

        $subscription = new Subscription;
        $subscription->subscriber_type = 'organization';
        $subscription->subscriber_id = 5;

        $this->assertFalse($policy->update($user, $entity, $subscription));
    }

    public function test_subscription_policy_update_denies_when_subscriber_id_mismatches(): void
    {
        $policy = new SubscriptionPolicy;
        $user = Mockery::mock('App\\Models\\User\\User');
        $entity = Mockery::mock(IsAnEntityContract::class);
        $entity->id = 5;
        $entity->shouldReceive('canUserManageEntity')->once()->with($user, Role::ADMINISTRATOR)->andReturn(true);
        $entity->shouldReceive('morphRelationName')->andReturn('user');

        $subscription = new Subscription;
        $subscription->subscriber_type = 'user';
        $subscription->subscriber_id = 999;

        $this->assertFalse($policy->update($user, $entity, $subscription));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
