<?php

declare(strict_types=1);

namespace Polis\Contracts\Repositories\Wiki;

use App\Models\User\User;
use App\Models\Wiki\Article;
use Polis\Contracts\Repositories\BaseRepositoryContract;

/**
 * Interface ArticleRepositoryContract
 */
interface ArticleRepositoryContract extends BaseRepositoryContract
{
    /**
     * Selects an article for a user based on their note completion status and article statistics
     */
    public function selectArticleForUser(User $user): ?Article;
}
