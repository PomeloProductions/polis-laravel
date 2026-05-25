<?php

declare(strict_types=1);

namespace Polis\Tests\Mocks;

use Polis\Http\Core\Requests\BaseAssetUploadRequestAbstract;

class AssetUploadRequest extends BaseAssetUploadRequestAbstract
{
    protected function getPolicyAction(): string
    {
        return '';
    }

    protected function getPolicyModel(): string
    {
        return '';
    }

    protected function getPolicyParameters(): array
    {
        return [];
    }
}
