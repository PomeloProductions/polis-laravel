<?php

declare(strict_types=1);

namespace Polis\Observers\Payment;

use App\Models\Payment\PaymentMethod;
use Illuminate\Contracts\Events\Dispatcher;
use Polis\Events\Payment\DefaultPaymentMethodSetEvent;

/**
 * Class PaymentMethodObserver
 */
class PaymentMethodObserver
{
    private Dispatcher $dispatcher;

    /**
     * PaymentMethodObserver constructor.
     */
    public function __construct(Dispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    public function created(PaymentMethod $paymentMethod)
    {
        if ($paymentMethod->default) {
            $this->dispatcher->dispatch(new DefaultPaymentMethodSetEvent($paymentMethod));
        }
    }

    public function updated(PaymentMethod $paymentMethod)
    {
        if ($paymentMethod->default) {
            $this->dispatcher->dispatch(new DefaultPaymentMethodSetEvent($paymentMethod));
        }
    }
}
