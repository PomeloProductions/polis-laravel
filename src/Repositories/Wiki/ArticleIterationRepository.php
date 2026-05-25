<?php

declare(strict_types=1);

namespace Polis\Repositories\Wiki;

use App\Models\Wiki\ArticleIteration;
use Polis\Contracts\Repositories\Wiki\ArticleIterationRepositoryContract;
use Polis\Repositories\BaseRepositoryAbstract;
use Polis\Repositories\Traits\NotImplemented\Delete;
use Psr\Log\LoggerInterface as LogContract;

/**
 * Class ArticleIterationRepository
 */
class ArticleIterationRepository extends BaseRepositoryAbstract implements ArticleIterationRepositoryContract
{
    use Delete, \Polis\Repositories\Traits\NotImplemented\Update;

    /**
     * IterationRepository constructor.
     */
    public function __construct(ArticleIteration $model, LogContract $log)
    {
        parent::__construct($model, $log);
    }
}
