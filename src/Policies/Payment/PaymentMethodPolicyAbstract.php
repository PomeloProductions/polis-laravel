<?php

declare(strict_types=1);

namespace Polis\Policies\Payment;

use App\Models\Payment\PaymentMethod;
use App\Models\Role;
use App\Models\User\User;
use Polis\Contracts\Models\IsAnEntityContract;
use Polis\Policies\BasePolicyAbstract;

abstract class PaymentMethodPolicyAbstract extends BasePolicyAbstract
{
    /**
     * @return bool
     */
    public function create(User $loggedInUser, IsAnEntityContract $entity)
    {
        return $entity->canUserManageEntity($loggedInUser, Role::ADMINISTRATOR);
    }

    /**
     * @return bool
     */
    public function update(User $loggedInUser, IsAnEntityContract $entity, PaymentMethod $paymentMethod)
    {
        return $entity->canUserManageEntity($loggedInUser, Role::ADMINISTRATOR)
            && $paymentMethod->owner_type == $entity->morphRelationName()
            && $paymentMethod->owner_id == $entity->id;
    }

    /**
     * @return bool
     */
    public function delete(User $loggedInUser, IsAnEntityContract $entity, PaymentMethod $paymentMethod)
    {
        return $entity->canUserManageEntity($loggedInUser, Role::ADMINISTRATOR)
            && $paymentMethod->owner_type == $entity->morphRelationName()
            && $paymentMethod->owner_id == $entity->id;
    }
}
