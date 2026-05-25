<?php

declare(strict_types=1);

namespace Polis\Tests\Integration\Policies\Payment;

use App\Models\Payment\PaymentMethod;
use App\Models\User\User;
use App\Policies\Payment\PaymentMethodPolicy;
use Polis\Tests\DatabaseSetupTrait;
use Polis\Tests\TestCase;

/**
 * Class PaymentMethodPolicyTest
 */
final class PaymentMethodPolicyTest extends TestCase
{
    use DatabaseSetupTrait;

    public function test_create(): void
    {
        $policy = new PaymentMethodPolicy;

        $me = User::factory()->create();
        $other = User::factory()->create();

        $this->assertFalse($policy->create($me, $other));
        $this->assertTrue($policy->create($me, $me));
    }

    public function test_update(): void
    {
        $policy = new PaymentMethodPolicy;

        $me = User::factory()->create();
        $other = User::factory()->create();

        $paymentMethod = PaymentMethod::factory()->create([
            'owner_id' => $me->id,
        ]);

        $this->assertFalse($policy->update($me, $other, $paymentMethod));
        $this->assertFalse($policy->update($other, $other, $paymentMethod));
        $this->assertTrue($policy->update($me, $me, $paymentMethod));
    }

    public function test_delete(): void
    {
        $policy = new PaymentMethodPolicy;

        $me = User::factory()->create();
        $other = User::factory()->create();

        $paymentMethod = PaymentMethod::factory()->create([
            'owner_id' => $me->id,
        ]);

        $this->assertFalse($policy->delete($me, $other, $paymentMethod));
        $this->assertFalse($policy->delete($other, $other, $paymentMethod));
        $this->assertTrue($policy->delete($me, $me, $paymentMethod));
    }
}
