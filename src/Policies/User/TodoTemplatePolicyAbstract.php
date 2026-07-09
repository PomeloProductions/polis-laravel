<?php

declare(strict_types=1);

namespace Polis\Policies\User;

use App\Models\User\User;
use Polis\Models\User\TodoTemplate;
use Polis\Policies\BasePolicyAbstract;

/**
 * Abstract policy for TodoTemplate. Consumers extend it as
 * App\Policies\User\TodoTemplatePolicy.
 */
abstract class TodoTemplatePolicyAbstract extends BasePolicyAbstract
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
