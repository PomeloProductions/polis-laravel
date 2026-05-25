<?php

declare(strict_types=1);

namespace Polis\Repositories\Collection;

use App\Models\Collection\CollectionItem;
use Polis\Contracts\Repositories\Collection\CollectionItemRepositoryContract;
use Polis\Repositories\BaseRepositoryAbstract;
use Psr\Log\LoggerInterface as LogContract;

class CollectionItemRepository extends BaseRepositoryAbstract implements CollectionItemRepositoryContract
{
    public function __construct(CollectionItem $model, LogContract $log)
    {
        parent::__construct($model, $log);
    }
}
