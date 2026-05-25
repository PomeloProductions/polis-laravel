<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Listeners\Payment;

use App\Models\Payment\PaymentMethod;
use App\Models\User\User;
use Polis\Contracts\Repositories\Payment\PaymentMethodRepositoryContract;
use Polis\Events\Payment\DefaultPaymentMethodSetEvent;
use Polis\Listeners\Payment\DefaultPaymentMethodSetListener;
use Polis\Tests\TestCase;

/**
 * Class DefaultPaymentMethodSetListenerTest
 */
final class DefaultPaymentMethodSetListenerTest extends TestCase
{
    public function test_handle(): void
    {
        $defaultPaymentMethod = new PaymentMethod([
            'owner' => new User([
                'paymentMethods' => collect([]),
            ]),
        ]);
        $defaultPaymentMethod->id = 142;

        $oldDefault = new PaymentMethod([
            'default' => true,
        ]);
        $oldDefault->id = 2342;

        $nonDefault = new PaymentMethod([
            'default' => false,
        ]);
        $nonDefault->id = 36;

        $defaultPaymentMethod->owner->paymentMethods->push($oldDefault);
        $defaultPaymentMethod->owner->paymentMethods->push($defaultPaymentMethod);
        $defaultPaymentMethod->owner->paymentMethods->push($nonDefault);

        $event = new DefaultPaymentMethodSetEvent($defaultPaymentMethod);

        $repository = mock(PaymentMethodRepositoryContract::class);
        $repository->shouldReceive('update')->once()->with($oldDefault, ['default' => false]);

        $listener = new DefaultPaymentMethodSetListener($repository);

        $listener->handle($event);
    }
}
