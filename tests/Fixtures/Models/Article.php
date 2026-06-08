<?php

declare(strict_types=1);

namespace Polis\Tests\Fixtures\Models;

/**
 * Fixture stub for App\Models\Wiki\Article.
 *
 * ArticlePolicyAbstract reads $created_by_id for ownership validation
 * on update; ArticleSummaryPolicyAbstract / ArticleVersionPolicyAbstract
 * also accept Article as a type hint.
 */
class Article
{
    public ?int $id = null;

    public ?int $created_by_id = null;
}

if (! class_exists(\App\Models\Wiki\Article::class, false)) {
    class_alias(
        Article::class,
        \App\Models\Wiki\Article::class,
    );
}
