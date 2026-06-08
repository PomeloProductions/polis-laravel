<?php

declare(strict_types=1);

namespace Polis\Tests\Fixtures\Models;

use Polis\Models\BaseModelAbstract;
use Polis\Tests\Fixtures\Traits\MockeryFriendlyAttributesTrait;

/**
 * Fixture stub for App\Models\Vote\Ballot.
 *
 * Extends BaseModelAbstract so it can pass through repository calls
 * (BallotCompletionRepositoryContract::create takes a BaseModelAbstract
 * for the related-model arg). See Category.php for the shared rationale.
 */
class Ballot extends BaseModelAbstract
{
    use MockeryFriendlyAttributesTrait;
}

if (! class_exists(\App\Models\Vote\Ballot::class, false)) {
    class_alias(
        Ballot::class,
        \App\Models\Vote\Ballot::class,
    );
}
