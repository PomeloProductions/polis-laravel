<?php

declare(strict_types=1);

namespace Polis\Tests\Fixtures\Models;

use Polis\Models\BaseModelAbstract;
use Polis\Tests\Fixtures\Traits\MockeryFriendlyAttributesTrait;

/**
 * Fixture stub for App\Models\Asset.
 *
 * Extends BaseModelAbstract because AssetRepositoryContract::update() and
 * ::delete() type-hint BaseModelAbstract. See Category.php for the shared
 * rationale.
 */
class Asset extends BaseModelAbstract
{
    use MockeryFriendlyAttributesTrait;
}

if (! class_exists(\App\Models\Asset::class, false)) {
    class_alias(
        Asset::class,
        \App\Models\Asset::class,
    );
}
