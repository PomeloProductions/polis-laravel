<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Models\Wiki;

use App\Models\Wiki\Article;
use App\Models\Wiki\ArticleSummary;
use Polis\Tests\TestCase;

/**
 * Class ArticleSummaryTest
 */
class ArticleSummaryTest extends TestCase
{
    public function test_article(): void
    {
        $summary = new ArticleSummary([
            'article_id' => 324,
        ]);
        $relation = $summary->article();
        $this->assertEquals('article_id', $relation->getForeignKeyName());
        $this->assertInstanceOf(Article::class, $relation->getRelated());
    }
}
