<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Services;

use App\Models\Payment\Payment;
use App\Models\Payment\PaymentMethod;
use App\Models\User\User;
use Carbon\Carbon;
use Cartalyst\Stripe\Api\Charges;
use Cartalyst\Stripe\Api\Refunds;
use Illuminate\Contracts\Events\Dispatcher;
use Polis\Contracts\Repositories\Payment\LineItemRepositoryContract;
use Polis\Contracts\Repositories\Payment\PaymentRepositoryContract;
use Polis\Events\Payment\PaymentReversedEvent;
use Polis\Exceptions\NotImplementedException;
use Polis\Services\StripePaymentService;
use Polis\Tests\CustomMockInterface;
use Polis\Tests\TestCase;

/**
 * Class StripePaymentServiceTest
 */
final class StripePaymentServiceTest extends TestCase
{
    /**
     * @var PaymentRepositoryContract|CustomMockInterface
     */
    private $paymentRepository;

    /**
     * @var LineItemRepositoryContract|CustomMockInterface
     */
    private $lineItemRepository;

    /**
     * @var Dispatcher|CustomMockInterface
     */
    private $dispatcher;

    /**
     * @var Charges|CustomMockInterface
     */
    private $chargeHandler;

    /**
     * @var Refunds|CustomMockInterface
     */
    private $refundHandler;

    /**
     * @var StripePaymentService
     */
    private $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->paymentRepository = mock(PaymentRepositoryContract::class);
        $this->lineItemRepository = mock(LineItemRepositoryContract::class);
        $this->dispatcher = mock(Dispatcher::class);
        $this->chargeHandler = mock(Charges::class);
        $this->refundHandler = mock(Refunds::class);

        $this->service = new StripePaymentService(
            $this->paymentRepository,
            $this->lineItemRepository,
            $this->dispatcher,
            $this->chargeHandler,
            $this->refundHandler
        );
    }

    public function test_create_payment(): void
    {
        $user = new User([
            'stripe_customer_key' => 'cus_test',
        ]);
        $user->id = 436;
        $paymentMethod = new PaymentMethod([
            'payment_method_key' => 'card_test',
        ]);
        $payment = new Payment([
            'amount' => 35.00,
        ]);

        $this->chargeHandler->shouldReceive('create')->once()->with([
            'amount' => 35.00,
            'currency' => 'usd',
            'capture' => true,
            'description' => 'A Description',
            'customer' => 'cus_test',
            'source' => 'card_test',
        ])->andReturn([
            'id' => 'tx_wegio',
            'source' => [
                'id' => 'card_test',
            ],
        ]);

        $this->paymentRepository->shouldReceive('create')->once()->with([
            'amount' => 35.00,
            'owner_id' => 436,
            'owner_type' => 'user',
            'transaction_key' => 'tx_wegio',
            'line_items' => [[
                'item_id' => 423,
                'item_type' => 'subscription',
                'amount' => 35.00,
            ]],
        ], $paymentMethod)->andReturn($payment);

        $result = $this->service->createPayment($user, $paymentMethod, 'A Description', [[
            'item_id' => 423,
            'item_type' => 'subscription',
            'amount' => 35.00,
        ]]);

        $this->assertEquals($result, $payment);
    }

    public function test_create_payment_with_zero_cost(): void
    {
        $user = new User;
        $user->id = 436;
        $paymentMethod = new PaymentMethod;
        $payment = new Payment([
            'amount' => 0.00,
        ]);

        $this->paymentRepository->shouldReceive('create')->once()->with([
            'amount' => 0,
            'owner_id' => 436,
            'owner_type' => 'user',
            'line_items' => [[
                'item_id' => 423,
                'item_type' => 'subscription',
                'amount' => 35,
            ], [
                'item_id' => 423,
                'item_type' => 'discount',
                'amount' => -35,
            ]],
        ], $paymentMethod)->andReturn($payment);

        $result = $this->service->createPayment($user, $paymentMethod, 'A Description', [[
            'item_id' => 423,
            'item_type' => 'subscription',
            'amount' => 35,
        ], [
            'item_id' => 423,
            'item_type' => 'discount',
            'amount' => -35,
        ]]);

        $this->assertEquals($result, $payment);
    }

    public function test_reverse_payment_fails_without_stripe(): void
    {
        $payment = new Payment([
            'paymentMethod' => new PaymentMethod,
        ]);

        $this->expectException(NotImplementedException::class);

        $this->service->reversePayment($payment);
    }

    public function test_reverse_payment_success(): void
    {
        $payment = new Payment([
            'paymentMethod' => new PaymentMethod([
                'payment_method_type' => 'stripe',
            ]),
            'transaction_key' => 'test_key',
        ]);

        Carbon::setTestNow('2019-01-01 12:00:00');

        $this->paymentRepository->shouldReceive('update')->once()
            ->with($payment, \Mockery::on(function ($data) {

                $this->assertArrayHasKey('refunded_at', $data);

                return true;
            })
            );
        $this->lineItemRepository->shouldReceive('create')->once();
        $this->refundHandler->shouldReceive('create')->once()->with('test_key');
        $this->dispatcher->shouldReceive('dispatch')->once()
            ->with(\Mockery::on(function (PaymentReversedEvent $event) use ($payment) {
                $this->assertEquals($event->getPayment(), $payment);

                return true;
            })
            );

        $this->service->reversePayment($payment);
    }

    public function test_issue_partial_refund_fails_without_stripe(): void
    {
        $payment = new Payment([
            'paymentMethod' => new PaymentMethod,
        ]);

        $this->expectException(NotImplementedException::class);

        $this->service->issuePartialRefund($payment, 5);
    }

    public function test_issue_partial_refund_success(): void
    {
        $payment = new Payment([
            'paymentMethod' => new PaymentMethod([
                'payment_method_type' => 'stripe',
            ]),
            'transaction_key' => 'test_key',
        ]);

        $this->refundHandler->shouldReceive('create')->once()->with('test_key', 5);

        $this->paymentRepository->shouldReceive('update')->once();
        $this->lineItemRepository->shouldReceive('create')->once();
        $this->service->issuePartialRefund($payment, 5);
    }
}
