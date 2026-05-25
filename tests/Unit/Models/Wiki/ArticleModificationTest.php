<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Models\Wiki;

use App\Models\Wiki\ArticleModification;
use Polis\Tests\TestCase;

/**
 * Class ArticleModificationTest
 */
final class ArticleModificationTest extends TestCase
{
    public function test_article(): void
    {
        $article = new ArticleModification;
        $relation = $article->article();

        $this->assertEquals('articles.id', $relation->getQualifiedOwnerKeyName());
        $this->assertEquals('article_modifications.article_id', $relation->getQualifiedForeignKeyName());
    }

    public function test_iterations(): void
    {
        $article = new ArticleModification;
        $relation = $article->iteration();

        $this->assertEquals('article_modifications.id', $relation->getQualifiedParentKeyName());
        $this->assertEquals('article_iterations.article_modification_id', $relation->getQualifiedForeignKeyName());
    }
}
