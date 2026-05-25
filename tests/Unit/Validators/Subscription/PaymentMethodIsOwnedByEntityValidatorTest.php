<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Validators\Subscription;

use App\Models\Organization\Organization;
use App\Models\Payment\PaymentMethod;
use App\Models\User\User;
use Cartalyst\Stripe\Exception\NotFoundException;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Polis\Contracts\Repositories\Payment\PaymentMethodRepositoryContract;
use Polis\Tests\TestCase;
use Polis\Validators\Subscription\PaymentMethodIsOwnedByEntityValidator;

/**
 * Class PaymentMethodIsOwnedByEntityValidatorTest
 */
final class PaymentMethodIsOwnedByEntityValidatorTest extends TestCase
{
    public function test_validate_fails_with_non_existing_rate(): void
    {
        $repository = mock(PaymentMethodRepositoryContract::class);
        $request = mock(Request::class);
        $validator = new PaymentMethodIsOwnedByEntityValidator($repository, $request);

        $repository->shouldReceive('findOrFail')->andThrow(new NotFoundException);

        $this->assertFalse($validator->validate('payment_method_id', 214));
    }

    public function test_validate_fails_with_mismatched_user(): void
    {
        $repository = mock(PaymentMethodRepositoryContract::class);
        $request = mock(Request::class);
        $validator = new PaymentMethodIsOwnedByEntityValidator($repository, $request);

        $paymentMethod = new PaymentMethod([
            'owner_id' => 3242,
            'owner_type' => 'user',
        ]);
        $repository->shouldReceive('findOrFail')->andReturn($paymentMethod);
        $user = new User;
        $user->id = 324;
        $request->shouldReceive('route')->with('user')->andReturn($user);
        $route = mock(Route::class);
        $route->shouldReceive('parameterNames')->andReturn([
            'user',
        ]);
        $request->shouldReceive('route')->andReturn($route);

        $this->assertFalse($validator->validate('payment_method_id', 214));
    }

    public function test_validate_fails_with_mismatched_owner_type(): void
    {
        $repository = mock(PaymentMethodRepositoryContract::class);
        $request = mock(Request::class);
        $validator = new PaymentMethodIsOwnedByEntityValidator($repository, $request);

        $paymentMethod = new PaymentMethod([
            'owner_id' => 3242,
            'owner_type' => 'organization',
        ]);
        $repository->shouldReceive('findOrFail')->andReturn($paymentMethod);
        $user = new User;
        $user->id = 3242;
        $request->shouldReceive('route')->with('user')->andReturn($user);
        $route = mock(Route::class);
        $route->shouldReceive('parameterNames')->andReturn([
            'user',
        ]);
        $request->shouldReceive('route')->andReturn($route);

        $this->assertFalse($validator->validate('payment_method_id', 214));
    }

    public function test_validate_passes_with_user(): void
    {
        $repository = mock(PaymentMethodRepositoryContract::class);
        $request = mock(Request::class);
        $validator = new PaymentMethodIsOwnedByEntityValidator($repository, $request);

        $paymentMethod = new PaymentMethod([
            'owner_id' => 3242,
            'owner_type' => 'user',
        ]);
        $repository->shouldReceive('findOrFail')->andReturn($paymentMethod);
        $user = new User;
        $user->id = 3242;
        $request->shouldReceive('route')->with('user')->andReturn($user);
        $route = mock(Route::class);
        $route->shouldReceive('parameterNames')->andReturn([
            'user',
        ]);
        $request->shouldReceive('route')->andReturn($route);

        $this->assertTrue($validator->validate('payment_method_id', 214));
    }

    public function test_validate_passes_with_organization(): void
    {
        $repository = mock(PaymentMethodRepositoryContract::class);
        $request = mock(Request::class);
        $validator = new PaymentMethodIsOwnedByEntityValidator($repository, $request);

        $paymentMethod = new PaymentMethod([
            'owner_id' => 3242,
            'owner_type' => 'organization',
        ]);
        $repository->shouldReceive('findOrFail')->andReturn($paymentMethod);
        $user = new Organization;
        $user->id = 3242;
        $request->shouldReceive('route')->with('organization')->andReturn($user);
        $route = mock(Route::class);
        $route->shouldReceive('parameterNames')->andReturn([
            'organization',
        ]);
        $request->shouldReceive('route')->andReturn($route);

        $this->assertTrue($validator->validate('payment_method_id', 214));
    }
}
