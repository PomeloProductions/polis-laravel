<?php

declare(strict_types=1);

namespace Polis\Tests\Fixtures\Models;

use Polis\Models\BaseModelAbstract;

/**
 * Fixture stub for App\Models\Wiki\ArticleVersion.
 *
 * Used by ArticleVersionRepositoryTest. See Article.php for the rationale.
 */
class ArticleVersion extends BaseModelAbstract
{
    use \Polis\Tests\Fixtures\Traits\MockeryFriendlyAttributesTrait;
}

if (! class_exists(\App\Models\Wiki\ArticleVersion::class, false)) {
    class_alias(
        ArticleVersion::class,
        \App\Models\Wiki\ArticleVersion::class,
    );
}
