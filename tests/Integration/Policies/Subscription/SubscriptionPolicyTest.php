<?php

declare(strict_types=1);

namespace Polis\Tests\Integration\Policies\Subscription;

use App\Models\Subscription\Subscription;
use App\Models\User\User;
use App\Policies\Subscription\SubscriptionPolicy;
use Polis\Tests\Application\ApplicationTestCase;

/**
 * Class SubscriptionPolicyTest
 */
final class SubscriptionPolicyTest extends ApplicationTestCase
{
    
    public function test_all(): void
    {
        $policy = new SubscriptionPolicy;

        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $this->assertFalse($policy->all($user1, $user2));
        $this->assertTrue($policy->all($user1, $user1));
    }

    public function test_create(): void
    {
        $policy = new SubscriptionPolicy;

        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $this->assertFalse($policy->create($user1, $user2));
        $this->assertTrue($policy->create($user1, $user1));
    }

    public function test_update(): void
    {
        $policy = new SubscriptionPolicy;

        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $subscription = Subscription::factory()->create([
            'subscriber_id' => $user1->id,
        ]);

        $this->assertFalse($policy->update($user1, $user2, $subscription));
        $this->assertFalse($policy->update($user2, $user2, $subscription));
        $this->assertTrue($policy->update($user1, $user1, $subscription));
    }
}
