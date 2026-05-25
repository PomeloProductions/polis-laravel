<?php

declare(strict_types=1);

namespace Polis\Services;

use App\Models\Payment\Payment;
use App\Models\Payment\PaymentMethod;
use Carbon\Carbon;
use Cartalyst\Stripe\Api\Charges;
use Cartalyst\Stripe\Api\Refunds;
use Illuminate\Contracts\Events\Dispatcher;
use Polis\Contracts\Models\IsAnEntityContract;
use Polis\Contracts\Repositories\Payment\LineItemRepositoryContract;
use Polis\Contracts\Repositories\Payment\PaymentRepositoryContract;
use Polis\Contracts\Services\StripePaymentServiceContract;
use Polis\Events\Payment\PaymentReversedEvent;
use Polis\Exceptions\NotImplementedException;
use Polis\Models\BaseModelAbstract;

/**
 * Class StripePaymentService
 */
class StripePaymentService implements StripePaymentServiceContract
{
    /**
     * @var PaymentRepositoryContract
     */
    private $paymentRepository;

    /**
     * @var LineItemRepositoryContract
     */
    private $lineItemRepository;

    /**
     * @var Dispatcher
     */
    private $dispatcher;

    /**
     * @var Charges
     */
    private $chargeHandler;

    /**
     * @var Refunds
     */
    private $refundHandler;

    /**
     * StripePaymentService constructor.
     */
    public function __construct(PaymentRepositoryContract $paymentRepository,
        LineItemRepositoryContract $lineItemRepository,
        Dispatcher $dispatcher, Charges $chargeHandler,
        Refunds $refundHandler)
    {
        $this->paymentRepository = $paymentRepository;
        $this->lineItemRepository = $lineItemRepository;
        $this->dispatcher = $dispatcher;
        $this->chargeHandler = $chargeHandler;
        $this->refundHandler = $refundHandler;
    }

    /**
     * @return array
     */
    public function captureCharge(float $amount, PaymentMethod $paymentMethod, string $description, ?string $customerKey = null)
    {
        $data = [
            'amount' => $amount,
            'currency' => 'usd',
            'capture' => true,
            'source' => $paymentMethod->payment_method_key,
            'description' => $description,
        ];

        if ($customerKey) {
            $data['customer'] = $customerKey;
        }

        return $this->chargeHandler->create($data);
    }

    /**
     * @return BaseModelAbstract|Payment
     */
    public function createPayment(IsAnEntityContract $entity, PaymentMethod $paymentMethod, string $description, array $lineItems): Payment
    {
        $amount = 0;
        foreach ($lineItems as $lineItem) {
            $amount += $lineItem['amount'];
        }
        $paymentData = [
            'amount' => $amount,
            'line_items' => $lineItems,
            'owner_id' => $entity->id,
            'owner_type' => $entity->morphRelationName(),
        ];

        if ($amount > 0) {
            $chargeData = $this->captureCharge($amount, $paymentMethod, $description, $entity->stripe_customer_key);
            if (isset($chargeData['id'])) {
                $paymentData['transaction_key'] = $chargeData['id'];
            }
        }

        return $this->paymentRepository->create($paymentData, $paymentMethod);
    }

    /**
     * Reverses a payment, and then triggers an accompanying PaymentReversed Event
     */
    public function reversePayment(Payment $payment)
    {
        if ($payment->paymentMethod->payment_method_type == 'stripe') {

            $this->refundHandler->create($payment->transaction_key);
        } else {
            throw new NotImplementedException('Only stripe transactions can be refunded right now');
        }

        $this->lineItemRepository->create([
            'amount' => -$payment->amount,
            'item_type' => 'refund',
        ], $payment);
        $this->paymentRepository->update($payment, [
            'refunded_at' => Carbon::now(),
            'amount' => 0,
        ]);

        $this->dispatcher->dispatch(new PaymentReversedEvent($payment));
    }

    /**
     * Issues a partial refund to the account the
     *
     * @return void
     */
    public function issuePartialRefund(Payment $payment, float $amount)
    {
        if ($payment->paymentMethod->payment_method_type == 'stripe') {

            $this->refundHandler->create($payment->transaction_key, $amount);

            $this->lineItemRepository->create([
                'amount' => -$amount,
                'item_type' => 'refund',
            ], $payment);
            $this->paymentRepository->update($payment, [
                'amount' => $payment->amount - $amount,
            ]);
        } else {
            throw new NotImplementedException('Only stripe transactions can be refunded right now');
        }
    }
}
