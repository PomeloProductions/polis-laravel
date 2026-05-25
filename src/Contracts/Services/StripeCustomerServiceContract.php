<?php

declare(strict_types=1);

namespace Polis\Contracts\Services;

use App\Models\Payment\PaymentMethod;
use Polis\Contracts\Models\IsAnEntityContract;

/**
 * Interface StripeCustomerServiceContract
 */
interface StripeCustomerServiceContract
{
    /**
     * Creates a new stripe customer for a user
     *
     * @return mixed
     */
    public function createCustomer(IsAnEntityContract $entity);

    /**
     * Retrieves a customer from stripe
     *
     * @return mixed
     */
    public function retrieveCustomer(IsAnEntityContract $entity);

    /**
     * Creates a new payment method
     *
     * @param  IsAnEntityContract  $hasPaymentMethod
     * @param  array  $paymentData
     * @return mixed
     */
    public function createPaymentMethod(IsAnEntityContract $entity, $paymentData): PaymentMethod;

    /**
     * Interacts with stripe in order to properly delete a user's card
     *
     * @return mixed
     */
    public function deletePaymentMethod(PaymentMethod $paymentMethod);

    /**
     * Interacts with stripe in order to properly retrieve information on a card
     *
     * @return mixed
     */
    public function retrievePaymentMethod(PaymentMethod $paymentMethod);
}
