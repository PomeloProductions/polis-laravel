<?php

declare(strict_types=1);

namespace Polis\Policies\User;

use App\Models\User\InvitationToken;
use App\Models\User\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Polis\Contracts\Policies\BasePolicyContract;
use Polis\Policies\BasePolicyAbstract;

abstract class InvitationTokenPolicyAbstract extends BasePolicyAbstract implements BasePolicyContract
{
    use HandlesAuthorization;

    public function all(User $user): bool
    {
        return false;
    }

    public function view(User $user, InvitationToken $invitationToken): bool
    {
        return false;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, InvitationToken $invitationToken): bool
    {
        return false;
    }

    public function delete(User $user, InvitationToken $invitationToken): bool
    {
        return false;
    }
}
