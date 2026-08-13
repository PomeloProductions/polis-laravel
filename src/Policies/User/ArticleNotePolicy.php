<?php

declare(strict_types=1);

namespace Polis\Policies\User;

use Polis\Providers\BaseAuthServiceProvider;

/**
 * Default concrete policy shipped by polis-laravel.
 *
 * This is the package's own fallback shim: it exists so a consumer
 * application does NOT have to create an empty `App\Policies\...Policy`
 * that merely extends the corresponding `ArticleNotePolicyAbstract`.
 *
 * When {@see BaseAuthServiceProvider::guessPolicyName()}
 * cannot find a consumer `App\Policies\...Policy` override it falls back to
 * this concrete class, which the Gate can instantiate. A consumer-supplied
 * `App\Policies\...Policy` still wins when present.
 */
class ArticleNotePolicy extends ArticleNotePolicyAbstract {}
