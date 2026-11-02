<?php

declare(strict_types=1);

namespace Polis\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;
use Polis\Tests\Fixtures\Traits\MockeryFriendlyAttributesTrait;

/**
 * Fixture stub for App\Models\Statistic\TargetStatisticDimension.
 *
 * Mirrors the lightweight (no soft-delete) package model so the Unit suite can
 * resolve the App\ FQCN without a consumer application on the classpath.
 */
class TargetStatisticDimension extends Model
{
    use MockeryFriendlyAttributesTrait;

    protected $guarded = [];
}

if (! class_exists(\App\Models\Statistic\TargetStatisticDimension::class, false)) {
    class_alias(
        TargetStatisticDimension::class,
        \App\Models\Statistic\TargetStatisticDimension::class,
    );
}
