<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Models\Wiki;

use App\Models\Wiki\Article;
use Polis\Tests\TestCase;

/**
 * Class ArticleTest
 */
final class ArticleTest extends TestCase
{
    public function test_created_by(): void
    {
        $article = new Article;
        $relation = $article->createdBy();

        $this->assertEquals('users.id', $relation->getQualifiedOwnerKeyName());
        $this->assertEquals('articles.created_by_id', $relation->getQualifiedForeignKeyName());
    }

    public function test_iterations(): void
    {
        $article = new Article;
        $relation = $article->iterations();

        $this->assertEquals('articles.id', $relation->getQualifiedParentKeyName());
        $this->assertEquals('article_iterations.article_id', $relation->getQualifiedForeignKeyName());

        $this->assertStringContainsString('order by', $relation->toSql());
        $this->assertStringContainsString('created_at', $relation->toSql());
        $this->assertStringContainsString('desc', $relation->toSql());
    }

    public function test_modifications(): void
    {
        $article = new Article;
        $relation = $article->modifications();

        $this->assertEquals('articles.id', $relation->getQualifiedParentKeyName());
        $this->assertEquals('article_modifications.article_id', $relation->getQualifiedForeignKeyName());

        $this->assertStringContainsString('order by', $relation->toSql());
        $this->assertStringContainsString('created_at', $relation->toSql());
        $this->assertStringContainsString('desc', $relation->toSql());
    }

    public function test_resource(): void
    {
        $user = new Article;
        $relation = $user->resource();

        $this->assertEquals('resources.resource_id', $relation->getQualifiedForeignKeyName());
        $this->assertEquals('resources.resource_type', $relation->getQualifiedMorphType());
        $this->assertEquals('articles.id', $relation->getQualifiedParentKeyName());
    }

    public function test_versions(): void
    {
        $article = new Article;
        $relation = $article->versions();

        $this->assertEquals('articles.id', $relation->getQualifiedParentKeyName());
        $this->assertEquals('article_versions.article_id', $relation->getQualifiedForeignKeyName());

        $this->assertStringContainsString('order by', $relation->toSql());
        $this->assertStringContainsString('created_at', $relation->toSql());
        $this->assertStringContainsString('desc', $relation->toSql());
    }
}
