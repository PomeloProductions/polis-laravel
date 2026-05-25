<?php

declare(strict_types=1);

namespace Polis\Contracts\Services\Asset;

use App\Models\Asset;
use Polis\Contracts\Models\IsAnEntityContract;

interface AssetImportServiceContract
{
    /**
     * imports an asset from a url and returns the data model
     */
    public function importAsset(IsAnEntityContract $owner, string $url): ?Asset;
}
