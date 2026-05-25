<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Models\Wiki;

use App\Models\Wiki\ArticleVersion;
use Polis\Tests\TestCase;

/**
 * Class ArticleVersionTest
 */
final class ArticleVersionTest extends TestCase
{
    public function test_article(): void
    {
        $article = new ArticleVersion;
        $relation = $article->article();

        $this->assertEquals('articles.id', $relation->getQualifiedOwnerKeyName());
        $this->assertEquals('article_versions.article_id', $relation->getQualifiedForeignKeyName());
    }

    public function test_article_iteration(): void
    {
        $article = new ArticleVersion;
        $relation = $article->articleIteration();

        $this->assertEquals('article_iterations.id', $relation->getQualifiedOwnerKeyName());
        $this->assertEquals('article_versions.article_iteration_id', $relation->getQualifiedForeignKeyName());
    }
}
