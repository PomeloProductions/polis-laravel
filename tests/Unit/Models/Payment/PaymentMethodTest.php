<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Models\Payment;

use App\Models\Payment\PaymentMethod;
use Polis\Tests\TestCase;

/**
 * Class PaymentMethodTest
 */
final class PaymentMethodTest extends TestCase
{
    public function test_payments(): void
    {
        $user = new PaymentMethod;
        $relation = $user->payments();

        $this->assertEquals('payment_methods.id', $relation->getQualifiedParentKeyName());
        $this->assertEquals('payments.payment_method_id', $relation->getQualifiedForeignKeyName());
    }

    public function test_subscriptions(): void
    {
        $user = new PaymentMethod;
        $relation = $user->subscriptions();

        $this->assertEquals('payment_methods.id', $relation->getQualifiedParentKeyName());
        $this->assertEquals('subscriptions.payment_method_id', $relation->getQualifiedForeignKeyName());
    }

    public function test_owner(): void
    {
        $model = new PaymentMethod;
        $relation = $model->owner();

        $this->assertEquals('payment_methods.owner_id', $relation->getQualifiedForeignKeyName());
        $this->assertEquals('owner_type', $relation->getMorphType());
    }
}
