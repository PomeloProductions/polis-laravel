<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Models\Subscription;

use App\Models\Subscription\MembershipPlan;
use App\Models\Subscription\MembershipPlanRate;
use App\Models\Subscription\Subscription;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Polis\Tests\TestCase;

/**
 * Class SubscriptionTest
 */
final class SubscriptionTest extends TestCase
{
    public function test_line_items(): void
    {
        $model = new Subscription;
        $relation = $model->lineItems();

        $this->assertEquals('line_items.item_id', $relation->getQualifiedForeignKeyName());
        $this->assertEquals('line_items.item_type', $relation->getQualifiedMorphType());
        $this->assertEquals('subscriptions.id', $relation->getQualifiedParentKeyName());
    }

    public function test_membership_plan_rate(): void
    {
        $model = new Subscription;
        $relation = $model->membershipPlanRate();

        $this->assertEquals('membership_plan_rates.id', $relation->getQualifiedOwnerKeyName());
        $this->assertEquals('subscriptions.membership_plan_rate_id', $relation->getQualifiedForeignKeyName());
    }

    public function test_payments(): void
    {
        $user = new Subscription;
        $relation = $user->payments();

        $this->assertEquals('subscriptions.id', $relation->getQualifiedParentKeyName());
        $this->assertEquals('line_items.item_id', $relation->getQualifiedForeignPivotKeyName());
        $this->assertEquals('line_items.payment_id', $relation->getQualifiedRelatedPivotKeyName());
        $this->assertEquals('item_type', $relation->getMorphType());
    }

    public function test_payment_method(): void
    {
        $model = new Subscription;
        $relation = $model->paymentMethod();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals('payment_methods.id', $relation->getQualifiedOwnerKeyName());
        $this->assertEquals('subscriptions.payment_method_id', $relation->getQualifiedForeignKeyName());
    }

    public function test_subscriber(): void
    {
        $model = new Subscription;
        $relation = $model->subscriber();

        $this->assertEquals('subscriptions.subscriber_id', $relation->getQualifiedForeignKeyName());
        $this->assertEquals('subscriber_type', $relation->getMorphType());
    }

    public function test_is_lifetime(): void
    {
        $yearSubscription = new Subscription([
            'membershipPlanRate' => new MembershipPlanRate([
                'membershipPlan' => new MembershipPlan([
                    'duration' => MembershipPlan::DURATION_YEAR,
                ]),
            ]),
        ]);

        $this->assertFalse($yearSubscription->isLifetime());

        $lifetimeSubscription = new Subscription([
            'membershipPlanRate' => new MembershipPlanRate([
                'membershipPlan' => new MembershipPlan([
                    'duration' => MembershipPlan::DURATION_LIFETIME,
                ]),
            ]),
        ]);

        $this->assertTrue($lifetimeSubscription->isLifetime());
    }

    public function test_formatted_expires_at(): void
    {
        $subscription = new Subscription;
        $this->assertNull($subscription->formatted_expires_at);

        $subscription = new Subscription([
            'expires_at' => new Carbon('2018-02-12'),
        ]);
        $this->assertEquals('February 12th 2018', $subscription->formatted_expires_at);
    }

    public function test_formatted_cost(): void
    {
        $subscription = new Subscription;
        $this->assertNull($subscription->formatted_cost);

        $subscription = new Subscription([
            'membershipPlanRate' => new MembershipPlanRate([
                'cost' => 1,
            ]),
        ]);
        $this->assertEquals('1.00', $subscription->formatted_cost);
    }
}
