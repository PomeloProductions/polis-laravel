<?php

declare(strict_types=1);

namespace Polis\Repositories;

use App\Models\Feature;
use Polis\Contracts\Repositories\FeatureRepositoryContract;
use Psr\Log\LoggerInterface as LogContract;

/**
 * Class FeatureRepository
 */
class FeatureRepository extends BaseRepositoryAbstract implements FeatureRepositoryContract
{
    /**
     * FeatureRepository constructor.
     */
    public function __construct(Feature $model, LogContract $log)
    {
        parent::__construct($model, $log);
    }
}
