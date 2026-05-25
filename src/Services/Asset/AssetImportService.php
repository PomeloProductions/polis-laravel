<?php

declare(strict_types=1);

namespace Polis\Services\Asset;

use App\Models\Asset;
use GuzzleHttp\Client;
use Polis\Contracts\Models\IsAnEntityContract;
use Polis\Contracts\Repositories\AssetRepositoryContract;
use Polis\Contracts\Services\Asset\AssetImportServiceContract;

class AssetImportService implements AssetImportServiceContract
{
    public function __construct(
        private AssetRepositoryContract $assetRepository,
        private Client $client,
    ) {}

    /**
     * imports an asset from a url and returns the data model
     *
     * @throws \ImagickException
     */
    public function importAsset(IsAnEntityContract $owner, string $url): ?Asset
    {
        $path = parse_url($url, PHP_URL_PATH);
        try {
            if ($path) {
                $fileInformation = pathinfo($path);

                $response = $this->client->get($url);
                $assetContent = $response->getStatusCode() == 200 ? $response->getBody()->getContents() : null;

                if ($assetContent !== null) {
                    return $this->assetRepository->create([
                        'source' => $url,
                        'file_contents' => $assetContent,
                        'file_extension' => $fileInformation['extension'],
                        'owner_type' => $owner->morphRelationName(),
                        'owner_id' => $owner->id,
                    ]);
                }
            }
        } catch (\Exception $e) {
        }

        return null;
    }
}
