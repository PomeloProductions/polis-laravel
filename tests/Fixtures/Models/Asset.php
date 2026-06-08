<?php

declare(strict_types=1);

namespace Polis\Tests\Fixtures\Models;

/**
 * Fixture stub for App\Models\Asset.
 *
 * AssetPolicyAbstract type-hints this on update/delete to assert the
 * asset's owner matches the requesting entity.
 */
class Asset
{
    public ?int $id = null;

    public ?string $owner_type = null;

    public ?int $owner_id = null;
}

if (! class_exists(\App\Models\Asset::class, false)) {
    class_alias(
        Asset::class,
        \App\Models\Asset::class,
    );
}
