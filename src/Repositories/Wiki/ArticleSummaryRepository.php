<?php

declare(strict_types=1);

namespace Polis\Repositories\Wiki;

use App\Models\Wiki\ArticleSummary;
use Polis\Contracts\Repositories\Wiki\ArticleSummaryRepositoryContract;
use Polis\Repositories\BaseRepositoryAbstract;
use Psr\Log\LoggerInterface as LogContract;

/**
 * Class ArticleSummaryRepository
 */
class ArticleSummaryRepository extends BaseRepositoryAbstract implements ArticleSummaryRepositoryContract
{
    /**
     * ArticleSummaryRepository constructor.
     */
    public function __construct(ArticleSummary $model, LogContract $log)
    {
        parent::__construct($model, $log);
    }
}
