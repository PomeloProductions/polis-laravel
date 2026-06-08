<?php

declare(strict_types=1);

namespace Polis\Tests\Fixtures\Models;

use Polis\Models\BaseModelAbstract;

/**
 * Fixture stub for App\Models\Statistic\Statistic.
 *
 * Extends BaseModelAbstract so repository tests that pass Statistic
 * mocks/instances to methods typed BaseModelAbstract (update/delete) work
 * without TypeError. The Statistic listener tests (which mocked Statistic
 * as a plain class via Mockery::mock(FQCN)) continue to work because
 * Mockery proxies the type either way.
 */
class Statistic extends BaseModelAbstract
{
    use \Polis\Tests\Fixtures\Traits\MockeryFriendlyAttributesTrait;
}

if (! class_exists(\App\Models\Statistic\Statistic::class, false)) {
    class_alias(
        Statistic::class,
        \App\Models\Statistic\Statistic::class,
    );
}
