<?php

declare(strict_types=1);

namespace Polis\Policies\Wiki;

use App\Models\Role;
use App\Models\User\User;
use Polis\Policies\BasePolicyAbstract;

abstract class ArticleIterationPolicyAbstract extends BasePolicyAbstract
{
    /**
     * @return bool
     */
    public function all(User $user)
    {
        return $user->hasRole([Role::ARTICLE_EDITOR, Role::ARTICLE_VIEWER]);
    }
}
