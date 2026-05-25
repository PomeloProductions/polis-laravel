<?php

declare(strict_types=1);

namespace Polis\Events\Payment;

use App\Models\Payment\Payment;

/**
 * Class PaymentReversedEvent
 */
class PaymentReversedEvent
{
    /**
     * @var Payment
     */
    private $payment;

    /**
     * PaymentReversedEvent constructor.
     */
    public function __construct(Payment $payment)
    {
        $this->payment = $payment;
    }

    public function getPayment(): Payment
    {
        return $this->payment;
    }
}
