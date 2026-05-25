<?php

declare(strict_types=1);

namespace Polis\Policies\User;

use App\Models\User\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Polis\Contracts\Models\HasPolicyContract;
use Polis\Contracts\Policies\BasePolicyContract;
use Polis\Policies\BasePolicyAbstract;

abstract class UserPolicyAbstract extends BasePolicyAbstract implements BasePolicyContract
{
    use HandlesAuthorization;

    /**
     * @var string can we view our self?
     */
    const ACTION_VIEW_SELF = 'view-self';

    /**
     * Only super admins can list all users
     */
    public function all(User $user): bool
    {
        return false;
    }

    /**
     * Determine if the user can view itself
     *
     * @return bool
     */
    public function viewSelf(User $user)
    {
        return true;
    }

    /**
     * Anyone can view a user
     *
     * @return bool
     */
    public function view(User $user, HasPolicyContract $model)
    {
        return true;
    }

    /**
     * Only super admins can create users
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Only the user themselves can update their profile
     *
     * @param  User|HasPolicyContract  $model
     * @return bool
     */
    public function update(User $user, HasPolicyContract $model)
    {
        return $user->id == $model->id;
    }

    /**
     * Only super admins can delete users
     */
    public function delete(User $user, User $model): bool
    {
        return false;
    }
}
