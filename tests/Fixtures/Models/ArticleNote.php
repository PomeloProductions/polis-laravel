<?php

declare(strict_types=1);

namespace Polis\Tests\Fixtures\Models;

use Polis\Models\BaseModelAbstract;
use Polis\Tests\Fixtures\Traits\MockeryFriendlyAttributesTrait;

/**
 * Fixture stub for App\Models\User\ArticleNote.
 *
 * Extends BaseModelAbstract so it can pass through
 * ArticleNoteRepositoryContract::update() and ::delete(). The static
 * ArticleNote::where(...) call inside randomArticle() is left
 * un-exercised by the test suite — testing that branch would require
 * mocking the static query builder which adds disproportionate plumbing.
 *
 * See Category.php for the shared rationale.
 */
class ArticleNote extends BaseModelAbstract
{
    use MockeryFriendlyAttributesTrait;
}

if (! class_exists(\App\Models\User\ArticleNote::class, false)) {
    class_alias(
        ArticleNote::class,
        \App\Models\User\ArticleNote::class,
    );
}
