<?php

declare(strict_types=1);

namespace Polis\Tests\Fixtures\Models;

use Polis\Models\BaseModelAbstract;

/**
 * Fixture stub for App\Models\Vote\Ballot.
 *
 * Extends BaseModelAbstract so BallotRepository tests can pass Ballot
 * instances/mocks to syncChildModels(BaseModelAbstract $parentModel) and
 * BaseRepositoryAbstract::update(BaseModelAbstract $model). Mixes in
 * MockeryFriendlyAttributesTrait so legacy policy tests' `$mock->id = 5`
 * patterns continue to work on Mockery doubles. See User.php for the
 * broader fixture rationale.
 *
 * BallotPolicyAbstract / BallotCompletionPolicyAbstract only use Ballot
 * as a type hint; the abstract gates do not read any properties off it.
 */
class Ballot extends BaseModelAbstract
{
    use \Polis\Tests\Fixtures\Traits\MockeryFriendlyAttributesTrait;
}

if (! class_exists(\App\Models\Vote\Ballot::class, false)) {
    class_alias(
        Ballot::class,
        \App\Models\Vote\Ballot::class,
    );
}
