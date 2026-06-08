<?php

declare(strict_types=1);

namespace Polis\Tests\Fixtures\Models;

use Polis\Models\BaseModelAbstract;
use Polis\Tests\Fixtures\Traits\MockeryFriendlyAttributesTrait;

/**
 * Fixture stub for App\Models\Messaging\Thread.
 *
 * Extends BaseModelAbstract so it can be passed through
 * MessageRepositoryContract::findAll's $belongsToArray (typed as
 * BaseModelAbstract[]). See Category.php for the shared rationale.
 */
class Thread extends BaseModelAbstract
{
    use MockeryFriendlyAttributesTrait;
}

if (! class_exists(\App\Models\Messaging\Thread::class, false)) {
    class_alias(
        Thread::class,
        \App\Models\Messaging\Thread::class,
    );
}
