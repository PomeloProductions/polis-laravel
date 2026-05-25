<?php

declare(strict_types=1);

namespace Polis\Policies\Wiki;

use App\Models\Role;
use App\Models\User\User;
use App\Models\Wiki\Article;
use Polis\Policies\BasePolicyAbstract;

abstract class ArticlePolicyAbstract extends BasePolicyAbstract
{
    /**
     * @return bool
     */
    public function all(User $user)
    {
        return true;
    }

    /**
     * @return bool
     */
    public function view(User $user, Article $model)
    {
        return true;
    }

    /**
     * @return bool
     */
    public function create(User $user)
    {
        return $user->hasRole(Role::ARTICLE_EDITOR);
    }

    /**
     * @return bool
     */
    public function update(User $user, Article $model)
    {
        return $user->hasRole(Role::ARTICLE_EDITOR) && $user->id == $model->created_by_id;
    }
}
