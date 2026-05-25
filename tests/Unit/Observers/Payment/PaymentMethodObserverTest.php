<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Observers\Payment;

use App\Models\Payment\PaymentMethod;
use Illuminate\Contracts\Events\Dispatcher;
use Polis\Observers\Payment\PaymentMethodObserver;
use Polis\Tests\CustomMockInterface;
use Polis\Tests\TestCase;

/**
 * Class PaymentMethodObserverTest
 */
final class PaymentMethodObserverTest extends TestCase
{
    /**
     * @var Dispatcher|CustomMockInterface
     */
    private $dispatcher;

    private PaymentMethodObserver $observer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dispatcher = mock(Dispatcher::class);
        $this->observer = new PaymentMethodObserver($this->dispatcher);
    }

    public function test_created(): void
    {
        $paymentMethod = new PaymentMethod([
            'default' => true,
        ]);

        $this->dispatcher->shouldReceive('dispatch')->once();

        $this->observer->created($paymentMethod);
    }

    public function test_updated(): void
    {
        $paymentMethod = new PaymentMethod([
            'default' => true,
        ]);

        $this->dispatcher->shouldReceive('dispatch')->once();

        $this->observer->updated($paymentMethod);
    }
}
