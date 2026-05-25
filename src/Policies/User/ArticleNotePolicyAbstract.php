<?php

declare(strict_types=1);

namespace Polis\Policies\User;

use App\Models\User\ArticleNote;
use App\Models\User\User;
use Polis\Policies\BasePolicyAbstract;

abstract class ArticleNotePolicyAbstract extends BasePolicyAbstract
{
    /**
     * @return bool
     */
    public function all(User $loggedInUser, User $requestedUser)
    {
        return $loggedInUser->id == $requestedUser->id;
    }

    /**
     * @return bool
     */
    public function create(User $loggedInUser, User $requestedUser)
    {
        return $loggedInUser->id == $requestedUser->id;
    }

    /**
     * @return bool
     */
    public function view(User $loggedInUser, User $requestedUser, ArticleNote $articleNote)
    {
        return $loggedInUser->id == $requestedUser->id &&
            $requestedUser->id == $articleNote->user_id;
    }

    /**
     * @return bool
     */
    public function update(User $loggedInUser, User $requestedUser, ArticleNote $articleNote)
    {
        return $loggedInUser->id == $requestedUser->id &&
            $requestedUser->id == $articleNote->user_id;
    }

    /**
     * @return bool
     */
    public function delete(User $loggedInUser, User $requestedUser, ArticleNote $articleNote)
    {
        return $loggedInUser->id == $requestedUser->id &&
            $requestedUser->id == $articleNote->user_id;
    }
}
