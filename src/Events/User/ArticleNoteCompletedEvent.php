<?php

declare(strict_types=1);

namespace Polis\Events\User;

use App\Models\User\ArticleNote;

/**
 * Class ArticleNoteCompletedEvent
 */
class ArticleNoteCompletedEvent
{
    private ArticleNote $articleNote;

    /**
     * ArticleNoteCompletedEvent constructor.
     */
    public function __construct(ArticleNote $articleNote)
    {
        $this->articleNote = $articleNote;
    }

    public function getArticleNote(): ArticleNote
    {
        return $this->articleNote;
    }
}
