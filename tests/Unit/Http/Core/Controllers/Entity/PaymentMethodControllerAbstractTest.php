<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Http\Core\Controllers\Entity;

use Illuminate\Http\JsonResponse;
use Mockery;
use Polis\Contracts\Models\IsAnEntityContract;
use Polis\Contracts\Repositories\Payment\PaymentMethodRepositoryContract;
use Polis\Contracts\Services\StripeCustomerServiceContract;
use Polis\Tests\Fixtures\Controllers\Entity\PaymentMethodController;
use Polis\Tests\Fixtures\Models\PaymentMethod as PaymentMethodFixture;
use Polis\Tests\Unit\Http\Core\Controllers\ControllerTestCase;

/**
 * Unit coverage for Entity\PaymentMethodControllerAbstract.
 *
 * store() bypasses the repository and delegates to the Stripe customer
 * service to register a card-token-backed payment method for the entity.
 */
final class PaymentMethodControllerAbstractTest extends ControllerTestCase
{
    public function test_store_creates_via_stripe_customer_service(): void
    {
        $repo = Mockery::mock(PaymentMethodRepositoryContract::class);
        $stripe = Mockery::mock(StripeCustomerServiceContract::class);
        $entity = Mockery::mock(IsAnEntityContract::class);

        $payload = ['token' => 'tok_visa_1234'];
        $request = $this->makeRequest(
            'App\\Http\\Core\\Requests\\Entity\\PaymentMethod\\StoreRequest',
            $payload,
        );

        $created = Mockery::mock(PaymentMethodFixture::class);
        $created->shouldReceive('toJson')->andReturn('{"id":1}');
        $stripe->shouldReceive('createPaymentMethod')
            ->once()
            ->with($entity, 'tok_visa_1234')
            ->andReturn($created);

        $response = (new PaymentMethodController($repo, $stripe))->store($request, $entity);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(201, $response->getStatusCode());
    }

    public function test_update_delegates_to_repository(): void
    {
        $repo = Mockery::mock(PaymentMethodRepositoryContract::class);
        $stripe = Mockery::mock(StripeCustomerServiceContract::class);
        $entity = Mockery::mock(IsAnEntityContract::class);

        $payload = ['payment_method_type' => 'card'];
        $request = $this->makeRequest(
            'App\\Http\\Core\\Requests\\Entity\\PaymentMethod\\UpdateRequest',
            $payload,
        );

        $paymentMethod = Mockery::mock(PaymentMethodFixture::class);
        $updated = Mockery::mock(PaymentMethodFixture::class);
        $repo->shouldReceive('update')->once()->with($paymentMethod, $payload)->andReturn($updated);

        $this->assertSame(
            $updated,
            (new PaymentMethodController($repo, $stripe))->update($request, $entity, $paymentMethod),
        );
    }

    public function test_destroy_deletes_and_returns_204(): void
    {
        $repo = Mockery::mock(PaymentMethodRepositoryContract::class);
        $stripe = Mockery::mock(StripeCustomerServiceContract::class);
        $entity = Mockery::mock(IsAnEntityContract::class);
        $request = $this->makeRequest('App\\Http\\Core\\Requests\\Entity\\PaymentMethod\\DeleteRequest');

        $paymentMethod = Mockery::mock(PaymentMethodFixture::class);
        $repo->shouldReceive('delete')->once()->with($paymentMethod);

        $response = (new PaymentMethodController($repo, $stripe))->destroy($request, $entity, $paymentMethod);
        $this->assertSame(204, $response->getStatusCode());
    }
}
