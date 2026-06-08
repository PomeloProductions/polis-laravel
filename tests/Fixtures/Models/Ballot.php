<?php

declare(strict_types=1);

namespace Polis\Tests\Fixtures\Models;

/**
 * Fixture stub for App\Models\Vote\Ballot.
 *
 * BallotPolicyAbstract / BallotCompletionPolicyAbstract use Ballot only
 * as a type hint; the abstract gates do not read any properties off it.
 */
class Ballot
{
    public ?int $id = null;
}

if (! class_exists(\App\Models\Vote\Ballot::class, false)) {
    class_alias(
        Ballot::class,
        \App\Models\Vote\Ballot::class,
    );
}
