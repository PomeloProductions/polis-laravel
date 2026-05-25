<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Services;

use App\Models\Organization\Organization;
use App\Models\Organization\OrganizationManager;
use App\Models\Payment\PaymentMethod;
use App\Models\Role;
use App\Models\User\User;
use Cartalyst\Stripe\Api\Cards;
use Cartalyst\Stripe\Api\Customers;
use Polis\Contracts\Repositories\Organization\OrganizationRepositoryContract;
use Polis\Contracts\Repositories\Payment\PaymentMethodRepositoryContract;
use Polis\Contracts\Repositories\User\UserRepositoryContract;
use Polis\Services\StripeCustomerService;
use Polis\Tests\CustomMockInterface;
use Polis\Tests\TestCase;

/**
 * Class StripeCustomerServiceTest
 */
final class StripeCustomerServiceTest extends TestCase
{
    /**
     * @var UserRepositoryContract|CustomMockInterface
     */
    private $userRepository;

    /**
     * @var OrganizationRepositoryContract|CustomMockInterface
     */
    private $organizationRepository;

    /**
     * @var PaymentMethodRepositoryContract|CustomMockInterface
     */
    private $paymentMethodRepository;

    /**
     * @var Customers|CustomMockInterface
     */
    private $customerHelper;

    /**
     * @var Cards|CustomMockInterface
     */
    private $cardHelper;

    /**
     * @var StripeCustomerService
     */
    private $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userRepository = mock(UserRepositoryContract::class);
        $this->organizationRepository = mock(OrganizationRepositoryContract::class);
        $this->paymentMethodRepository = mock(PaymentMethodRepositoryContract::class);
        $this->customerHelper = mock(Customers::class);
        $this->cardHelper = mock(Cards::class);
        $this->service = new StripeCustomerService(
            $this->userRepository,
            $this->organizationRepository,
            $this->paymentMethodRepository,
            $this->customerHelper,
            $this->cardHelper
        );
    }

    public function test_create_customer_with_user(): void
    {
        $user = new User([
            'first_name' => 'John',
            'last_name' => 'Stewart',
            'email' => 'test@test.com',
        ]);
        $user->id = 234;

        $this->customerHelper->shouldReceive('create')->once()->with([
            'name' => 'John Stewart',
            'email' => 'test@test.com',
            'description' => 'User ID - 234',
        ])->andReturn([
            'id' => 'cus_test',
            'sources' => [],
        ]);

        $this->userRepository->shouldReceive('update')->once()->with($user, ['stripe_customer_key' => 'cus_test']);

        $result = $this->service->createCustomer($user);

        $this->assertEquals($result, [
            'id' => 'cus_test',
            'sources' => [],
        ]);
        $this->assertEquals('cus_test', $user->stripe_customer_key);
    }

    public function test_create_customer_with_organization_without_managers(): void
    {
        $user = new Organization([
            'name' => 'An Organization',
            'email' => 'test@test.com',
            'organizationManagers' => collect([]),
        ]);
        $user->id = 234;

        $this->customerHelper->shouldReceive('create')->once()->with([
            'name' => 'An Organization',
            'email' => null,
            'description' => 'Organization ID - 234',
        ])->andReturn([
            'id' => 'cus_test',
            'sources' => [],
        ]);

        $this->userRepository->shouldReceive('update')->once()->with($user, ['stripe_customer_key' => 'cus_test']);

        $result = $this->service->createCustomer($user);

        $this->assertEquals($result, [
            'id' => 'cus_test',
            'sources' => [],
        ]);
        $this->assertEquals('cus_test', $user->stripe_customer_key);
    }

    public function test_create_customer_with_organization_with_managers(): void
    {
        $user = new Organization([
            'name' => 'An Organization',
            'email' => 'test@test.com',
            'organizationManagers' => collect([
                new OrganizationManager([
                    'role_id' => Role::MANAGER,
                    'user' => new User([
                        'email' => 'anemail@test.com',
                    ]),
                ]),
                new OrganizationManager([
                    'role_id' => Role::ADMINISTRATOR,
                    'user' => new User([
                        'email' => 'theemail@test.com',
                    ]),
                ]),
            ]),
        ]);
        $user->id = 234;

        $this->customerHelper->shouldReceive('create')->once()->with([
            'name' => 'An Organization',
            'email' => 'theemail@test.com',
            'description' => 'Organization ID - 234',
        ])->andReturn([
            'id' => 'cus_test',
            'sources' => [],
        ]);

        $this->userRepository->shouldReceive('update')->once()->with($user, ['stripe_customer_key' => 'cus_test']);

        $result = $this->service->createCustomer($user);

        $this->assertEquals($result, [
            'id' => 'cus_test',
            'sources' => [],
        ]);
        $this->assertEquals('cus_test', $user->stripe_customer_key);
    }

    public function test_retrieve_customer_fails_without_token(): void
    {
        $user = new User;

        $this->expectException(\InvalidArgumentException::class);

        $this->service->retrieveCustomer($user);
    }

    public function test_retrieve_customer(): void
    {
        $user = new User([
            'stripe_customer_key' => 'cus_test',
        ]);

        $this->customerHelper->shouldReceive('find')->once()->andReturn([
            'id' => 'cus_test',
            'sources' => [],
        ]);

        $result = $this->service->retrieveCustomer($user);

        $this->assertEquals($result, [
            'id' => 'cus_test',
            'sources' => [],
        ]);
    }

    public function test_create_payment_method_without_existing_stripe_customer(): void
    {
        $user = new User([
            'first_name' => 'John',
            'last_name' => 'Stewart',
            'email' => 'test@test.com',
        ]);
        $user->id = 324;

        $this->customerHelper->shouldReceive('create')->once()->with([
            'name' => 'John Stewart',
            'email' => 'test@test.com',
            'description' => 'User ID - 324',
        ])->andReturn([
            'id' => 'cus_test',
            'sources' => [],
        ]);

        $this->userRepository->shouldReceive('update')->once()->with($user, ['stripe_customer_key' => 'cus_test']);

        $this->cardHelper->shouldReceive('create')->once()->with('cus_test', [
            'card_number',
        ])->andReturn([
            'id' => 'card_id',
            'last4' => '1234',
            'brand' => 'Visa',
            'exp_month' => 8,
            'exp_year' => 2032,
        ]);

        $this->paymentMethodRepository->shouldReceive('create')->once()->with([
            'payment_method_key' => 'card_id',
            'payment_method_type' => 'stripe',
            'identifier' => '1234',
            'brand' => 'Visa',
            'exp_month' => '08',
            'exp_year' => '2032',
            'owner_id' => 324,
            'owner_type' => 'user',
        ])->andReturn(new PaymentMethod);

        $this->service->createPaymentMethod($user, [
            'card_number',
        ]);
    }

    public function test_create_payment_method_with_existing_stripe_customer(): void
    {
        $user = new User([
            'email' => 'test@test.com',
            'stripe_customer_key' => 'cus_test',
        ]);
        $user->id = 324;

        $this->cardHelper->shouldReceive('create')->once()->with('cus_test', [
            'card_number',
        ])->andReturn([
            'id' => 'card_id',
            'last4' => '1234',
            'brand' => 'Visa',
            'exp_month' => 10,
            'exp_year' => 2032,
        ]);

        $this->paymentMethodRepository->shouldReceive('create')->once()->with([
            'payment_method_key' => 'card_id',
            'payment_method_type' => 'stripe',
            'identifier' => '1234',
            'brand' => 'Visa',
            'exp_month' => '10',
            'exp_year' => '2032',
            'owner_id' => 324,
            'owner_type' => 'user',
        ])->andReturn(new PaymentMethod);

        $this->service->createPaymentMethod($user, [
            'card_number',
        ]);
    }

    public function test_delete_payment_method_fails_without_token(): void
    {
        $paymentMethod = new PaymentMethod([
            'owner' => new User,
        ]);

        $this->expectException(\InvalidArgumentException::class);

        $this->service->deletePaymentMethod($paymentMethod);
    }

    public function test_delete_payment_method(): void
    {
        $paymentMethod = new PaymentMethod([
            'payment_method_key' => 'card_test',
            'owner' => new User([
                'stripe_customer_key' => 'cus_test',
            ]),
        ]);

        $this->paymentMethodRepository->shouldReceive('delete')->once()->with($paymentMethod);
        $this->cardHelper->shouldReceive('delete')->once()->with('cus_test', 'card_test')->andReturn([
            'id' => 'card',
        ]);

        $result = $this->service->deletePaymentMethod($paymentMethod);

        $this->assertEquals($result, [
            'id' => 'card',
        ]);
    }

    public function test_retrieve_payment_method_returns_null_without_card(): void
    {
        $paymentMethod = new PaymentMethod([
            'payment_method_key' => 'card_test',
            'owner' => new User([
                'stripe_customer_key' => null,
            ]),
        ]);

        $this->assertNull($this->service->retrievePaymentMethod($paymentMethod));
    }

    public function test_retrieve_payment_method(): void
    {
        $paymentMethod = new PaymentMethod([
            'payment_method_key' => 'card',
            'owner' => new User([
                'stripe_customer_key' => 'customer',
            ]),
        ]);

        $this->cardHelper->shouldReceive('find')->once()->with('customer', 'card')->andReturn([
            'last4' => '4242',
        ]);

        $result = $this->service->retrievePaymentMethod($paymentMethod);

        $this->assertEquals($result, [
            'last4' => '4242',
        ]);
    }
}
