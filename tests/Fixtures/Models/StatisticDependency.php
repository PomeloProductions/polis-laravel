<?php

declare(strict_types=1);

namespace Polis\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;
use Polis\Tests\Fixtures\Traits\MockeryFriendlyAttributesTrait;

/**
 * Fixture stub for App\Models\Statistic\StatisticDependency.
 *
 * Mirrors the lightweight (no soft-delete) package model so the Unit suite can
 * resolve the App\ FQCN without a consumer application on the classpath.
 */
class StatisticDependency extends Model
{
    use MockeryFriendlyAttributesTrait;

    protected $guarded = [];
}

if (! class_exists(\App\Models\Statistic\StatisticDependency::class, false)) {
    class_alias(
        StatisticDependency::class,
        \App\Models\Statistic\StatisticDependency::class,
    );
}
