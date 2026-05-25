<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Models\Payment;

use App\Models\Payment\Payment;
use Polis\Tests\TestCase;

/**
 * Class PaymentTest
 */
final class PaymentTest extends TestCase
{
    public function test_line_items(): void
    {
        $model = new Payment;
        $relation = $model->lineItems();

        $this->assertEquals('payments.id', $relation->getQualifiedParentKeyName());
        $this->assertEquals('line_items.payment_id', $relation->getQualifiedForeignKeyName());
    }

    public function test_owner(): void
    {
        $model = new Payment;
        $relation = $model->owner();

        $this->assertEquals('payments.owner_id', $relation->getQualifiedForeignKeyName());
        $this->assertEquals('owner_type', $relation->getMorphType());
    }

    public function test_payment_method(): void
    {
        $model = new Payment;
        $relation = $model->paymentMethod();

        $this->assertEquals('payment_methods.id', $relation->getQualifiedOwnerKeyName());
        $this->assertEquals('payments.payment_method_id', $relation->getQualifiedForeignKeyName());
    }
}
