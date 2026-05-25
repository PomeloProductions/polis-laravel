<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Models\Subscription;

use App\Models\Subscription\MembershipPlanRate;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Polis\Tests\TestCase;

/**
 * Class MembershipPlanRateTest
 */
final class MembershipPlanRateTest extends TestCase
{
    public function test_membership_plans(): void
    {
        $model = new MembershipPlanRate;
        $relation = $model->membershipPlan();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals('membership_plans.id', $relation->getQualifiedOwnerKeyName());
        $this->assertEquals('membership_plan_rates.membership_plan_id', $relation->getQualifiedForeignKeyName());
    }

    public function test_subscriptions(): void
    {
        $user = new MembershipPlanRate;
        $relation = $user->subscriptions();

        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertEquals('membership_plan_rates.id', $relation->getQualifiedParentKeyName());
        $this->assertEquals('subscriptions.membership_plan_rate_id', $relation->getQualifiedForeignKeyName());
    }
}
