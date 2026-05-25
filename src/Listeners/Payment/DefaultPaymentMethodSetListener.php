<?php

declare(strict_types=1);

namespace Polis\Listeners\Payment;

use Polis\Contracts\Repositories\Payment\PaymentMethodRepositoryContract;
use Polis\Events\Payment\DefaultPaymentMethodSetEvent;

/**
 * Class DefaultPaymentMethodSetListener
 */
class DefaultPaymentMethodSetListener
{
    private PaymentMethodRepositoryContract $paymentMethodRepository;

    /**
     * DefaultPaymentMethodSetListener constructor.
     */
    public function __construct(PaymentMethodRepositoryContract $paymentMethodRepository)
    {
        $this->paymentMethodRepository = $paymentMethodRepository;
    }

    public function handle(DefaultPaymentMethodSetEvent $event)
    {
        $defaultPaymentMethod = $event->getPaymentMethod();

        foreach ($defaultPaymentMethod->owner->paymentMethods as $paymentMethod) {
            if ($paymentMethod->id != $defaultPaymentMethod->id && $paymentMethod->default) {
                $this->paymentMethodRepository->update($paymentMethod, [
                    'default' => false,
                ]);
            }
        }
    }
}
