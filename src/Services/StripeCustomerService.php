<?php

declare(strict_types=1);

namespace Polis\Services;

use App\Models\Organization\Organization;
use App\Models\Organization\OrganizationManager;
use App\Models\Payment\PaymentMethod;
use App\Models\Role;
use App\Models\User\User;
use Cartalyst\Stripe\Api\Cards;
use Cartalyst\Stripe\Api\Customers;
use InvalidArgumentException;
use Polis\Contracts\Models\IsAnEntityContract;
use Polis\Contracts\Repositories\Organization\OrganizationRepositoryContract;
use Polis\Contracts\Repositories\Payment\PaymentMethodRepositoryContract;
use Polis\Contracts\Repositories\User\UserRepositoryContract;
use Polis\Contracts\Services\StripeCustomerServiceContract;
use Polis\Exceptions\NotImplementedException;
use Polis\Models\BaseModelAbstract;

/**
 * Class StripeCustomerService
 */
class StripeCustomerService implements StripeCustomerServiceContract
{
    private UserRepositoryContract $userRepository;

    private OrganizationRepositoryContract $organizationRepository;

    private PaymentMethodRepositoryContract $paymentMethodRepository;

    private Customers $customerHelper;

    private Cards $cardHelper;

    /**
     * StripeCustomerService constructor.
     */
    public function __construct(UserRepositoryContract $userRepository,
        OrganizationRepositoryContract $organizationRepository,
        PaymentMethodRepositoryContract $paymentMethodRepository,
        Customers $customerHelper, Cards $cardHelper)
    {
        $this->userRepository = $userRepository;
        $this->organizationRepository = $organizationRepository;
        $this->paymentMethodRepository = $paymentMethodRepository;
        $this->customerHelper = $customerHelper;
        $this->cardHelper = $cardHelper;
    }

    /**
     * Creates a new stripe customer for a user
     *
     * @return mixed
     */
    public function createCustomer(IsAnEntityContract $entity)
    {
        if ($entity->morphRelationName() == 'user') {
            /** @var User $entity */
            $customerData = [
                'email' => $entity->email,
                'name' => $entity->first_name.' '.$entity->last_name,
                'description' => 'User ID - '.$entity->id,
            ];
            $repository = $this->userRepository;
        } elseif ($entity->morphRelationName() == 'organization') {
            /** @var Organization $entity */

            /** @var OrganizationManager|null $organizationAdmin */
            $organizationAdmin = $entity->organizationManagers->filter(function (OrganizationManager $manager) {
                return $manager->role_id == Role::ADMINISTRATOR;
            })->first();

            $customerData = [
                'email' => $organizationAdmin ? $organizationAdmin->user->email : null,
                'name' => $entity->name,
                'description' => 'Organization ID - '.$entity->id,
            ];
            $repository = $this->userRepository;
            // Add more possible payment method owners here
        } else {
            throw new NotImplementedException('Please make sure to setup your other payment method owners before interacting with stripe');
        }
        $data = $this->customerHelper->create($customerData);

        $repository->update($entity, [
            'stripe_customer_key' => $data['id'],
        ]);

        $entity->stripe_customer_key = $data['id'];

        return $data;
    }

    /**
     * Retrieves a customer from stripe
     *
     * @return mixed
     */
    public function retrieveCustomer(IsAnEntityContract $entity)
    {
        if (! $entity->stripe_customer_key) {
            throw new InvalidArgumentException('The passed in user does not have a stripe customer key associated with their account.');
        }

        return $this->customerHelper->find($entity->stripe_customer_key);
    }

    /**
     * Creates a new payment method
     *
     * @param  BaseModelAbstract|IsAnEntityContract  $entity
     * @param  array  $paymentData
     * @return mixed
     */
    public function createPaymentMethod(IsAnEntityContract $entity, $paymentData): PaymentMethod
    {
        if (! $entity->stripe_customer_key) {
            $this->createCustomer($entity);
        }

        $data = $this->cardHelper->create($entity->stripe_customer_key, $paymentData);

        return $this->paymentMethodRepository->create([
            'payment_method_key' => $data['id'],
            'payment_method_type' => 'stripe',
            'identifier' => $data['last4'],
            'exp_month' => ($data['exp_month'] < 10 ? '0' : '').$data['exp_month'],
            'exp_year' => ''.$data['exp_year'],
            'brand' => $data['brand'],
            'owner_id' => $entity->id,
            'owner_type' => $entity->morphRelationName(),
        ]);
    }

    /**
     * Interacts with stripe in order to properly delete a user's card
     *
     * @return mixed
     */
    public function deletePaymentMethod(PaymentMethod $paymentMethod)
    {
        if (! $paymentMethod->owner->stripe_customer_key) {
            throw new InvalidArgumentException('The passed in user does not have a stripe customer key associated with their account.');
        }

        $this->paymentMethodRepository->delete($paymentMethod);

        return $this->cardHelper->delete($paymentMethod->owner->stripe_customer_key, $paymentMethod->payment_method_key);
    }

    /**
     * Interacts with stripe in order to properly retrieve information on a card
     *
     * @return mixed
     */
    public function retrievePaymentMethod(PaymentMethod $paymentMethod)
    {
        if ($paymentMethod->owner->stripe_customer_key) {
            return $this->cardHelper->find($paymentMethod->owner->stripe_customer_key, $paymentMethod->payment_method_key);
        }

        return null;
    }
}
