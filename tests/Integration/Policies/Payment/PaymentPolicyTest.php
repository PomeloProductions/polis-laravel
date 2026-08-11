<?php

declare(strict_types=1);

namespace Polis\Tests\Integration\Policies\Payment;

use App\Models\User\User;
use App\Policies\Payment\PaymentPolicy;
use Polis\Tests\Application\ApplicationTestCase;

/**
 * Class SubscriptionPolicyTest
 */
final class PaymentPolicyTest extends ApplicationTestCase
{
    
    public function test_all(): void
    {
        $policy = new PaymentPolicy;

        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $this->assertFalse($policy->all($user1, $user2));
        $this->assertTrue($policy->all($user1, $user1));
    }
}
