<?php

declare(strict_types=1);

namespace Polis\Tests\Fixtures\Models;

use Polis\Models\BaseModelAbstract;
use Polis\Tests\Fixtures\Traits\MockeryFriendlyAttributesTrait;

/**
 * Fixture stub for App\Models\Wiki\ArticleSummary.
 *
 * Extends BaseModelAbstract because ArticleSummaryRepositoryContract::update()
 * and ::delete() type-hint BaseModelAbstract. See Category.php for the
 * shared rationale.
 */
class ArticleSummary extends BaseModelAbstract
{
    use MockeryFriendlyAttributesTrait;
}

if (! class_exists(\App\Models\Wiki\ArticleSummary::class, false)) {
    class_alias(
        ArticleSummary::class,
        \App\Models\Wiki\ArticleSummary::class,
    );
}
