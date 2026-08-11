<?php

declare(strict_types=1);

namespace App\Policies\User;

use App\Models\User\TodoTemplate;
use App\Models\User\User;
use Polis\Policies\BasePolicyAbstract;

class TodoTemplatePolicy extends BasePolicyAbstract
{
    public function all(User $loggedInUser, User $requestedUser): bool
    {
        return $loggedInUser->id === $requestedUser->id;
    }

    public function view(User $loggedInUser, User $requestedUser, TodoTemplate $template): bool
    {
        return $loggedInUser->id === $requestedUser->id && $template->user_id === $requestedUser->id;
    }

    public function create(User $loggedInUser, User $requestedUser): bool
    {
        return $loggedInUser->id === $requestedUser->id;
    }

    public function update(User $loggedInUser, User $requestedUser, TodoTemplate $template): bool
    {
        return $loggedInUser->id === $requestedUser->id && $template->user_id === $requestedUser->id;
    }

    public function delete(User $loggedInUser, User $requestedUser, TodoTemplate $template): bool
    {
        return $loggedInUser->id === $requestedUser->id && $template->user_id === $requestedUser->id;
    }
}
