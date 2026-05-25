<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Models;

use App\Models\Asset;
use Polis\Tests\TestCase;

/**
 * Class AssetTest
 */
final class AssetTest extends TestCase
{
    public function test_owner(): void
    {
        $model = new Asset;
        $relation = $model->owner();

        $this->assertEquals('assets.owner_id', $relation->getQualifiedForeignKeyName());
    }
}
