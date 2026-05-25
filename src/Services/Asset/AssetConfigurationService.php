<?php

declare(strict_types=1);

namespace Polis\Services\Asset;

use Polis\Contracts\Services\Asset\AssetConfigurationServiceContract;

class AssetConfigurationService implements AssetConfigurationServiceContract
{
    public function __construct(private string $serverUrl, private string $baseAssetDirectory) {}

    public function getServerUrl(): string
    {
        return $this->serverUrl;
    }

    /**
     * @todo this probably needs to be moved to the model
     */
    public function getBaseAssetDirectory(): string
    {
        return $this->baseAssetDirectory;
    }
}
