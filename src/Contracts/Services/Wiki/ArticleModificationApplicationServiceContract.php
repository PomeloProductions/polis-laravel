<?php

declare(strict_types=1);

namespace Polis\Contracts\Services\Wiki;

use App\Models\User\User;
use App\Models\Wiki\Article;
use App\Models\Wiki\ArticleModification;

/**
 * Interface ArticleModificationApplicationServiceContract
 */
interface ArticleModificationApplicationServiceContract
{
    /**
     * Runs all changes needed for the past through article modification
     */
    public function applyModification(User $user, ArticleModification $articleModification): ?Article;

    /**
     * Handles a remove action based on the modification passed through
     */
    public function handleRemoveAction(User $user, ArticleModification $articleModification): ?Article;

    /**
     * Handles an add action based on the modification passed through
     */
    public function handleAddAction(User $user, ArticleModification $articleModification): ?Article;

    /**
     * Handles a replace action based on the modification passed through
     */
    public function handleReplaceAction(User $user, ArticleModification $articleModification): ?Article;
}
