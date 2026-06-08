<?php

declare(strict_types=1);

namespace Polis\Tests\Fixtures\Models;

use Polis\Models\BaseModelAbstract;
use Polis\Tests\Fixtures\Traits\MockeryFriendlyAttributesTrait;

/**
 * Fixture stub for App\Models\Statistic\Statistic.
 *
 * Extends BaseModelAbstract because StatisticRepositoryContract::update()
 * and ::delete() type-hint BaseModelAbstract, and the controller calls
 * $statistic->load() in show(). See Category.php for the shared rationale.
 */
class Statistic extends BaseModelAbstract
{
    use MockeryFriendlyAttributesTrait;
}

if (! class_exists(\App\Models\Statistic\Statistic::class, false)) {
    class_alias(
        Statistic::class,
        \App\Models\Statistic\Statistic::class,
    );
}
