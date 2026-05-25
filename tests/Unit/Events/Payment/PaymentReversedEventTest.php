<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Events\Payment;

use App\Models\Payment\Payment;
use Polis\Events\Payment\PaymentReversedEvent;
use Polis\Tests\TestCase;

/**
 * Class PaymentReversedEventTest
 */
final class PaymentReversedEventTest extends TestCase
{
    public function test_get_payment(): void
    {
        $payment = new Payment;

        $event = new PaymentReversedEvent($payment);

        $this->assertEquals($payment, $event->getPayment());
    }
}
