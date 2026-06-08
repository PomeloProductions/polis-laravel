<?php

declare(strict_types=1);

namespace Polis\Tests\Fixtures\Models;

use Polis\Models\BaseModelAbstract;

/**
 * Fixture stub for App\Models\Statistic\TargetStatistic.
 *
 * Extends BaseModelAbstract so the TargetStatisticRepository's
 * findForTarget()/createForTarget() return-type ?TargetStatistic can be
 * satisfied with `new TargetStatistic` in tests.
 */
class TargetStatistic extends BaseModelAbstract
{
    use \Polis\Tests\Fixtures\Traits\MockeryFriendlyAttributesTrait;
}

if (! class_exists(\App\Models\Statistic\TargetStatistic::class, false)) {
    class_alias(
        TargetStatistic::class,
        \App\Models\Statistic\TargetStatistic::class,
    );
}
