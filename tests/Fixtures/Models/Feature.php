<?php

declare(strict_types=1);

namespace Polis\Tests\Fixtures\Models;

use Polis\Models\BaseModelAbstract;
use Polis\Tests\Fixtures\Traits\MockeryFriendlyAttributesTrait;

/**
 * Fixture stub for App\Models\Feature.
 *
 * Extends BaseModelAbstract so $model->load() round-trips through the
 * Eloquent stack (FeatureController::show calls it). See Category.php for
 * the shared rationale.
 */
class Feature extends BaseModelAbstract
{
    use MockeryFriendlyAttributesTrait;
}

if (! class_exists(\App\Models\Feature::class, false)) {
    class_alias(
        Feature::class,
        \App\Models\Feature::class,
    );
}
