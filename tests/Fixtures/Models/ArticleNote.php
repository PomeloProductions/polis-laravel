<?php

declare(strict_types=1);

namespace Polis\Tests\Fixtures\Models;

/**
 * Fixture stub for App\Models\User\ArticleNote.
 *
 * ArticleNotePolicyAbstract reads $user_id off this model to validate
 * the note belongs to the requested user.
 */
class ArticleNote
{
    public ?int $id = null;

    public ?int $user_id = null;
}

if (! class_exists(\App\Models\User\ArticleNote::class, false)) {
    class_alias(
        ArticleNote::class,
        \App\Models\User\ArticleNote::class,
    );
}
