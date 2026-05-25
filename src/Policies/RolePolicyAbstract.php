<?php

declare(strict_types=1);

namespace Polis\Policies;

use App\Models\User\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Polis\Contracts\Policies\BasePolicyContract;

abstract class RolePolicyAbstract extends BasePolicyAbstract implements BasePolicyContract
{
    use HandlesAuthorization;

    /**
     * No one can see this besides super admins, which are already caught in the parent before
     */
    public function all(User $user): bool
    {
        return false;
    }
}
