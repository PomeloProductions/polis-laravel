<?php

declare(strict_types=1);

namespace Polis\Tests\Fixtures\Models;

use Polis\Models\BaseModelAbstract;

/**
 * Fixture stub for App\Models\Wiki\Article.
 *
 * Extends BaseModelAbstract for the ArticleRepository tests, which pass
 * Article mocks to update()/delete() (typed BaseModelAbstract) and need
 * the sync('categories', ...) pivot to be callable on a mock instance.
 *
 * ArticlePolicyAbstract reads $created_by_id for ownership validation
 * on update; ArticleSummaryPolicyAbstract / ArticleVersionPolicyAbstract
 * also accept Article as a type hint. These flow through Eloquent's
 * dynamic attribute getter/setter (no explicit property needed).
 */
class Article extends BaseModelAbstract
{
    use \Polis\Tests\Fixtures\Traits\MockeryFriendlyAttributesTrait;
}

if (! class_exists(\App\Models\Wiki\Article::class, false)) {
    class_alias(
        Article::class,
        \App\Models\Wiki\Article::class,
    );
}
