<?php

declare(strict_types=1);

namespace Polis\Policies\Wiki;

use App\Models\Role;
use App\Models\User\User;
use App\Models\Wiki\Article;
use Polis\Policies\BasePolicyAbstract;

abstract class ArticleVersionPolicyAbstract extends BasePolicyAbstract
{
    /**
     * @return bool
     */
    public function all(User $user)
    {
        return $user->hasRole([Role::ARTICLE_VIEWER, Role::ARTICLE_EDITOR]);
    }

    /**
     * @return bool
     */
    public function create(User $user, Article $article)
    {
        return $user->id == $article->created_by_id;
    }
}
