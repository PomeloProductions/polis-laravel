<?php

declare(strict_types=1);

namespace Polis\Events\Payment;

use App\Models\Payment\PaymentMethod;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Class DefaultPaymentMethodSetEvent
 */
class DefaultPaymentMethodSetEvent implements ShouldQueue
{
    use Queueable;

    private PaymentMethod $paymentMethod;

    /**
     * DefaultPaymentMethodSetEvent constructor.
     */
    public function __construct(PaymentMethod $paymentMethod)
    {
        $this->paymentMethod = $paymentMethod;
    }

    public function getPaymentMethod(): PaymentMethod
    {
        return $this->paymentMethod;
    }
}
