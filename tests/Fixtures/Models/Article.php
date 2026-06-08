<?php

declare(strict_types=1);

namespace Polis\Tests\Fixtures\Models;

use Polis\Models\BaseModelAbstract;
use Polis\Tests\Fixtures\Traits\MockeryFriendlyAttributesTrait;

/**
 * Fixture stub for App\Models\Wiki\Article.
 *
 * Extends BaseModelAbstract because ArticleRepositoryContract::update()
 * requires a BaseModelAbstract instance, and the controller forwards the
 * App\Models\Wiki\Article it receives directly. See Category.php for the
 * shared rationale.
 */
class Article extends BaseModelAbstract
{
    use MockeryFriendlyAttributesTrait;
}

if (! class_exists(\App\Models\Wiki\Article::class, false)) {
    class_alias(
        Article::class,
        \App\Models\Wiki\Article::class,
    );
}
