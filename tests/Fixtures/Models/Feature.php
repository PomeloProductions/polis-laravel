<?php

declare(strict_types=1);

namespace Polis\Tests\Fixtures\Models;

/**
 * Fixture stub for App\Models\Feature.
 *
 * Used by FeaturePolicyAbstract::view() — fixture only needs to satisfy
 * the type hint.
 */
class Feature
{
    public ?int $id = null;
}

if (! class_exists(\App\Models\Feature::class, false)) {
    class_alias(
        Feature::class,
        \App\Models\Feature::class,
    );
}
