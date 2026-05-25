<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Events\Payment;

use App\Models\Payment\PaymentMethod;
use Polis\Events\Payment\DefaultPaymentMethodSetEvent;
use Polis\Tests\TestCase;

/**
 * Class DefaultPaymentMethodSetEventTest
 */
final class DefaultPaymentMethodSetEventTest extends TestCase
{
    public function test_get_payment_method(): void
    {
        $paymentMethod = new PaymentMethod;

        $event = new DefaultPaymentMethodSetEvent($paymentMethod);

        $this->assertEquals($paymentMethod, $event->getPaymentMethod());
    }
}
