<?php

declare(strict_types=1);

namespace Polis\Jobs;

use App\Models\Asset;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Contracts\Queue\ShouldQueue;
use Imagick;
use Polis\Contracts\Repositories\AssetRepositoryContract;
use Polis\Contracts\Services\Asset\AssetConfigurationServiceContract;

class CalculateAssetDimensionsJob implements ShouldQueue
{
    use Queueable;

    public function __construct(private Asset $asset) {}

    /**
     * Calculates our actual dimensions for us
     *
     * @return void
     *
     * @throws \ImagickException
     */
    public function handle(
        AssetRepositoryContract $assetRepository,
        Factory $fileSystem,
        AssetConfigurationServiceContract $assetConfigurationService,
    ) {
        $publicDisk = $fileSystem->disk('public');

        $assetDirectory = $assetConfigurationService->getBaseAssetDirectory().'/';
        $parts = explode(
            $assetDirectory,
            $this->asset->url,
        );
        $fileName = $assetDirectory.end($parts);
        $fileContents = $publicDisk->get($fileName);

        if ($fileContents) {

            $image = new Imagick;
            $image->readImageBlob($fileContents);
            $width = $image->getImageWidth();
            $height = $image->getImageHeight();

            $assetRepository->update($this->asset, [
                'width' => $width,
                'height' => $height,
            ]);
        }
    }
}
