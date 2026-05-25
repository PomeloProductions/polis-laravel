<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Models\User;

use App\Models\User\ArticleNote;
use Polis\Tests\TestCase;

/**
 * Class ArticleNoteTest
 */
final class ArticleNoteTest extends TestCase
{
    public function test_user(): void
    {
        $articleNote = new ArticleNote;
        $relation = $articleNote->user();

        $this->assertEquals('article_notes.user_id', $relation->getQualifiedForeignKeyName());
        $this->assertEquals('users.id', $relation->getQualifiedOwnerKeyName());
    }

    public function test_article(): void
    {
        $articleNote = new ArticleNote;
        $relation = $articleNote->article();

        $this->assertEquals('article_notes.article_id', $relation->getQualifiedForeignKeyName());
        $this->assertEquals('articles.id', $relation->getQualifiedOwnerKeyName());
    }
}
