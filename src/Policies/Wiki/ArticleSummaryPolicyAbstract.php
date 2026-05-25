<?php

declare(strict_types=1);

namespace Polis\Policies\Wiki;

use App\Models\Role;
use App\Models\User\User;
use App\Models\Wiki\Article;
use Polis\Policies\BasePolicyAbstract;

abstract class ArticleSummaryPolicyAbstract extends BasePolicyAbstract
{
    /**
     * @return bool
     */
    public function view(User $user, Article $article)
    {
        return true;
    }

    /**
     * @return bool
     */
    public function create(User $user, Article $article)
    {
        return $user->hasRole(Role::ARTICLE_EDITOR);
    }

    /**
     * @return bool
     */
    public function update(User $user, Article $article)
    {
        return $user->hasRole(Role::ARTICLE_EDITOR);
    }

    /**
     * @return bool
     */
    public function delete(User $user, Article $article)
    {
        return $user->hasRole(Role::ARTICLE_EDITOR);
    }
}
