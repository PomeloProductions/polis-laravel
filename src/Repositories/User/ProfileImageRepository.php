<?php

declare(strict_types=1);

namespace Polis\Repositories\User;

use App\Models\User\ProfileImage;
use Illuminate\Contracts\Filesystem\Factory;
use Polis\Contracts\Repositories\User\ProfileImageRepositoryContract;
use Polis\Contracts\Services\Asset\AssetConfigurationServiceContract;
use Polis\Repositories\AssetRepository;
use Polis\Repositories\Traits\NotImplemented\Delete;
use Polis\Repositories\Traits\NotImplemented\Update;
use Psr\Log\LoggerInterface as LogContract;

/**
 * Class ProfileImageRepository
 */
class ProfileImageRepository extends AssetRepository implements ProfileImageRepositoryContract
{
    use Delete, \Polis\Repositories\Traits\NotImplemented\FindAll, Update;

    /**
     * ProfileImageRepository constructor.
     */
    public function __construct(
        ProfileImage $model,
        LogContract $log,
        Factory $fileSystem,
        AssetConfigurationServiceContract $assetConfigurationService,
    ) {
        parent::__construct($model, $log, $fileSystem, $assetConfigurationService);
    }
}
