<?php

declare(strict_types=1);

namespace Polis\Contracts\Services\Asset;

interface AssetConfigurationServiceContract
{
    /**
     * Gets the URL for the server where the assets live
     */
    public function getServerUrl(): string;

    /**
     * Gets the directory where all assets live on the server
     */
    public function getBaseAssetDirectory(): string;
}
