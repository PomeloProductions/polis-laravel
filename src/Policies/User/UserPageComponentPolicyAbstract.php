<?php

declare(strict_types=1);

namespace Polis\Policies\User;

use App\Models\User\User;
use App\Models\User\UserPage;
use App\Models\User\UserPageComponent;
use Polis\Policies\BasePolicyAbstract;

abstract class UserPageComponentPolicyAbstract extends BasePolicyAbstract
{
    public function all(User $loggedInUser, User $requestedUser, UserPage $page): bool
    {
        return $loggedInUser->id === $requestedUser->id && $page->user_id === $requestedUser->id;
    }

    public function create(User $loggedInUser, User $requestedUser, UserPage $page): bool
    {
        return $loggedInUser->id === $requestedUser->id && $page->user_id === $requestedUser->id;
    }

    public function update(User $loggedInUser, User $requestedUser, UserPage $page, UserPageComponent $component): bool
    {
        return $loggedInUser->id === $requestedUser->id
            && $page->user_id === $requestedUser->id
            && $component->user_page_id === $page->id;
    }

    public function delete(User $loggedInUser, User $requestedUser, UserPage $page, UserPageComponent $component): bool
    {
        return $loggedInUser->id === $requestedUser->id
            && $page->user_id === $requestedUser->id
            && $component->user_page_id === $page->id;
    }
}
