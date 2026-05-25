<?php

declare(strict_types=1);

namespace Polis\Contracts\Services;

use App\Models\Payment\Payment;
use App\Models\Payment\PaymentMethod;
use Polis\Contracts\Models\IsAnEntityContract;

/**
 * Interface StripePaymentServiceContract
 */
interface StripePaymentServiceContract
{
    /**
     * @return array
     */
    public function captureCharge(float $amount, PaymentMethod $paymentMethod, string $description, ?string $customerKey = null);

    /**
     * @return mixed
     */
    public function createPayment(IsAnEntityContract $entity, PaymentMethod $paymentMethod, string $description, array $lineItems): Payment;

    /**
     * Reverses a payment, and then triggers an accompanying PaymentReversed Event
     *
     * @return mixed
     */
    public function reversePayment(Payment $payment);

    /**
     * Issues a partial refund to the account the
     *
     * @return mixed
     */
    public function issuePartialRefund(Payment $payment, float $amount);
}
