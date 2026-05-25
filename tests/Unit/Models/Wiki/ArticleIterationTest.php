<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Models\Wiki;

use App\Models\Wiki\ArticleIteration;
use Polis\Tests\TestCase;

/**
 * Class IterationTest
 */
final class ArticleIterationTest extends TestCase
{
    public function test_article(): void
    {
        $article = new ArticleIteration;
        $relation = $article->article();

        $this->assertEquals('articles.id', $relation->getQualifiedOwnerKeyName());
        $this->assertEquals('article_iterations.article_id', $relation->getQualifiedForeignKeyName());
    }

    public function test_created_by(): void
    {
        $article = new ArticleIteration;
        $relation = $article->createdBy();

        $this->assertEquals('users.id', $relation->getQualifiedOwnerKeyName());
        $this->assertEquals('article_iterations.created_by_id', $relation->getQualifiedForeignKeyName());
    }

    public function test_modification(): void
    {
        $article = new ArticleIteration;
        $relation = $article->modification();

        $this->assertEquals('article_modifications.id', $relation->getQualifiedOwnerKeyName());
        $this->assertEquals('article_iterations.article_modification_id', $relation->getQualifiedForeignKeyName());
    }

    public function test_versions(): void
    {
        $article = new ArticleIteration;
        $relation = $article->version();

        $this->assertEquals('article_iterations.id', $relation->getQualifiedParentKeyName());
        $this->assertEquals('article_versions.article_iteration_id', $relation->getQualifiedForeignKeyName());
    }
}
