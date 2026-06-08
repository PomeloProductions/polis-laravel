<?php

declare(strict_types=1);

namespace Polis\Services;

use App\Models\Payment\PaymentMethod;
use Polis\Contracts\Models\IsAnEntityContract;
use Polis\Contracts\Services\StripeCustomerServiceContract;
use Polis\Providers\BaseServiceProvider;

/**
 * No-op default implementation of {@see StripeCustomerServiceContract}.
 *
 * polis-laravel type-hints {@see StripeCustomerServiceContract} from
 * controllers and other services. Consumer applications that do not
 * integrate with Stripe (no subscription billing, no card storage) should
 * not have to provide a working Stripe implementation just to satisfy the
 * container.
 *
 * This service is bound by {@see BaseServiceProvider}
 * via `bindIf()` so any consumer that does want Stripe can register its
 * own implementation (e.g. {@see StripeCustomerService})
 * and that binding wins.
 *
 * Every method here is a no-op: methods returning `mixed` return `null`,
 * and `createPaymentMethod()` returns a fresh empty `PaymentMethod` so the
 * declared `: PaymentMethod` return type is honoured without touching
 * Stripe or the database.
 */
class NoopStripeCustomerService implements StripeCustomerServiceContract
{
    /**
     * No-op default — override by binding a real implementation if your app uses Stripe.
     *
     * @return mixed
     */
    public function createCustomer(IsAnEntityContract $entity)
    {
        return null;
    }

    /**
     * No-op default — override by binding a real implementation if your app uses Stripe.
     *
     * @return mixed
     */
    public function retrieveCustomer(IsAnEntityContract $entity)
    {
        return null;
    }

    /**
     * No-op default — override by binding a real implementation if your app uses Stripe.
     *
     * Returns a fresh empty {@see PaymentMethod} so the declared return
     * type is honoured without touching Stripe or the database.
     *
     * @param  array  $paymentData
     */
    public function createPaymentMethod(IsAnEntityContract $entity, $paymentData): PaymentMethod
    {
        return new PaymentMethod;
    }

    /**
     * No-op default — override by binding a real implementation if your app uses Stripe.
     *
     * @return mixed
     */
    public function deletePaymentMethod(PaymentMethod $paymentMethod)
    {
        return null;
    }

    /**
     * No-op default — override by binding a real implementation if your app uses Stripe.
     *
     * @return mixed
     */
    public function retrievePaymentMethod(PaymentMethod $paymentMethod)
    {
        return null;
    }
}
