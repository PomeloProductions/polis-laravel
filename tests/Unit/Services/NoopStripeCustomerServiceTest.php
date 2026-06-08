<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Services;

use App\Models\Payment\PaymentMethod;
use Polis\Contracts\Models\IsAnEntityContract;
use Polis\Contracts\Services\StripeCustomerServiceContract;
use Polis\Providers\BaseServiceProvider;
use Polis\Services\NoopStripeCustomerService;
use Polis\Tests\TestCase;
use Polis\Tests\Unit\Providers\BaseServiceProviderAbstractFallbackTest;

/**
 * Verifies that polis-laravel ships a working no-op default binding for
 * {@see StripeCustomerServiceContract}.
 *
 * The base service provider can't be booted in isolation (it references
 * many other consumer-app classes via App\* type hints) so this test
 * replays the same `bindIf()` closure used in
 * {@see BaseServiceProvider::register()} — exactly the
 * pattern already established by
 * {@see BaseServiceProviderAbstractFallbackTest}.
 *
 * If the binding in BaseServiceProvider drifts (e.g. swaps to a different
 * default or stops using bindIf), this test should drift with it: that's
 * the contract we promise to consumer apps.
 */
final class NoopStripeCustomerServiceTest extends TestCase
{
    public function test_default_binding_resolves_to_noop_stripe_customer_service(): void
    {
        $this->app->bindIf(
            StripeCustomerServiceContract::class,
            fn () => new NoopStripeCustomerService,
        );

        $resolved = $this->app->make(StripeCustomerServiceContract::class);

        $this->assertInstanceOf(NoopStripeCustomerService::class, $resolved);
        $this->assertInstanceOf(StripeCustomerServiceContract::class, $resolved);
    }

    public function test_bindif_lets_consumer_explicit_binding_win(): void
    {
        // Consumer registers first…
        $consumerImpl = new class implements StripeCustomerServiceContract
        {
            public function createCustomer(IsAnEntityContract $entity)
            {
                return 'consumer-create';
            }

            public function retrieveCustomer(IsAnEntityContract $entity)
            {
                return 'consumer-retrieve';
            }

            public function createPaymentMethod(IsAnEntityContract $entity, $paymentData): PaymentMethod
            {
                return new PaymentMethod;
            }

            public function deletePaymentMethod(PaymentMethod $paymentMethod)
            {
                return 'consumer-delete';
            }

            public function retrievePaymentMethod(PaymentMethod $paymentMethod)
            {
                return 'consumer-retrievepm';
            }
        };
        $this->app->bind(StripeCustomerServiceContract::class, fn () => $consumerImpl);

        // …then BaseServiceProvider's bindIf runs and MUST NOT clobber it.
        $this->app->bindIf(
            StripeCustomerServiceContract::class,
            fn () => new NoopStripeCustomerService,
        );

        $resolved = $this->app->make(StripeCustomerServiceContract::class);

        $this->assertNotInstanceOf(NoopStripeCustomerService::class, $resolved);
        $this->assertSame('consumer-create', $resolved->createCustomer(
            $this->makeEntity(),
        ));
    }

    public function test_create_customer_returns_null(): void
    {
        $this->assertNull(
            (new NoopStripeCustomerService)->createCustomer($this->makeEntity()),
        );
    }

    public function test_retrieve_customer_returns_null(): void
    {
        $this->assertNull(
            (new NoopStripeCustomerService)->retrieveCustomer($this->makeEntity()),
        );
    }

    public function test_create_payment_method_returns_fresh_empty_payment_method(): void
    {
        $result = (new NoopStripeCustomerService)->createPaymentMethod(
            $this->makeEntity(),
            ['number' => '4242424242424242'],
        );

        // Honours the declared : PaymentMethod return type without touching
        // Stripe or the database.
        $this->assertInstanceOf(PaymentMethod::class, $result);
    }

    public function test_delete_payment_method_returns_null(): void
    {
        $this->assertNull(
            (new NoopStripeCustomerService)->deletePaymentMethod(new PaymentMethod),
        );
    }

    public function test_retrieve_payment_method_returns_null(): void
    {
        $this->assertNull(
            (new NoopStripeCustomerService)->retrievePaymentMethod(new PaymentMethod),
        );
    }

    public function test_implements_stripe_customer_service_contract(): void
    {
        $this->assertInstanceOf(
            StripeCustomerServiceContract::class,
            new NoopStripeCustomerService,
        );
    }

    private function makeEntity(): IsAnEntityContract
    {
        return mock(IsAnEntityContract::class);
    }
}
