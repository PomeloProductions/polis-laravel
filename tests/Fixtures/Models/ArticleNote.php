<?php

declare(strict_types=1);

namespace Polis\Tests\Fixtures\Models;

use Polis\Models\BaseModelAbstract;

/**
 * Fixture stub for App\Models\User\ArticleNote.
 *
 * Extends BaseModelAbstract so ArticleNoteRepository tests can pass
 * ArticleNote mocks to create()/update() (typed BaseModelAbstract).
 * Mixes in MockeryFriendlyAttributesTrait so legacy policy tests'
 * `$mock->id = 5` / `$mock->user_id = 7` patterns continue to work on
 * Mockery doubles. See User.php for the broader fixture rationale.
 *
 * ArticleNotePolicyAbstract reads $user_id off this model to validate
 * the note belongs to the requested user — handled via Eloquent's
 * attribute getter on real instances and the trait's attribute-store
 * fast-path on Mockery mocks.
 */
class ArticleNote extends BaseModelAbstract
{
    use \Polis\Tests\Fixtures\Traits\MockeryFriendlyAttributesTrait;
}

if (! class_exists(\App\Models\User\ArticleNote::class, false)) {
    class_alias(
        ArticleNote::class,
        \App\Models\User\ArticleNote::class,
    );
}
