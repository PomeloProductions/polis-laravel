<?php

declare(strict_types=1);

namespace Polis\Policies\Payment;

use App\Models\User\User;
use Polis\Contracts\Models\IsAnEntityContract;
use Polis\Policies\BasePolicyAbstract;

abstract class PaymentPolicyAbstract extends BasePolicyAbstract
{
    /**
     * @return bool
     */
    public function all(User $loggedInUser, IsAnEntityContract $entity)
    {
        return $entity->canUserManageEntity($loggedInUser);
    }
}
