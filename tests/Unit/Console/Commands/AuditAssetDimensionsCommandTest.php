<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Console\Commands;

use App\Models\Asset;
use Illuminate\Contracts\Bus\Dispatcher;
use Polis\Console\Commands\AuditAssetDimensionsCommand;
use Polis\Contracts\Repositories\AssetRepositoryContract;
use Polis\Tests\TestCase;

class AuditAssetDimensionsCommandTest extends TestCase
{
    public function test_handle()
    {
        $dispatcher = mock(Dispatcher::class);
        $assetRepository = mock(AssetRepositoryContract::class);

        $command = new AuditAssetDimensionsCommand($dispatcher, $assetRepository);

        $assetRepository->shouldReceive('findAll')->andReturn(collect([
            new Asset,
            new Asset,
            new Asset,
        ]));

        $dispatcher->shouldReceive('dispatch')->times(3);

        $command->handle();
    }
}
