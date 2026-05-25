<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Services\Asset;

use Polis\Services\Asset\AssetConfigurationService;
use Polis\Tests\TestCase;

class AssetConfigurationServiceTest extends TestCase
{
    public function test_get_server_url()
    {
        $service = new AssetConfigurationService(
            'http://hello.bye',
            'assets',
        );

        $this->assertEquals('http://hello.bye', $service->getServerUrl());
    }

    public function test_get_base_asset_directory()
    {
        $service = new AssetConfigurationService(
            'http://hello.bye',
            'assets',
        );

        $this->assertEquals('assets', $service->getBaseAssetDirectory());
    }
}
