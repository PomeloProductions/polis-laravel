<?php

declare(strict_types=1);

namespace Polis\Repositories\Payment;

use App\Models\Payment\LineItem;
use Polis\Contracts\Repositories\Payment\LineItemRepositoryContract;
use Polis\Repositories\BaseRepositoryAbstract;
use Psr\Log\LoggerInterface as LogContract;

/**
 * Class LineItemRepository
 */
class LineItemRepository extends BaseRepositoryAbstract implements LineItemRepositoryContract
{
    /**
     * LineItemRepository constructor.
     */
    public function __construct(LineItem $model, LogContract $log)
    {
        parent::__construct($model, $log);
    }
}
