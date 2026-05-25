<?php

declare(strict_types=1);

namespace Polis\Contracts\Models;

use App\Models\User\User;

interface CanBeManagedByEntityContract
{
    public function canUserManage(User $loggedInUser, IsAnEntityContract $entity, string $action): bool;
}
