<?php

declare(strict_types=1);

namespace Polis\Repositories\Wiki;

use App\Models\Wiki\ArticleModification;
use Polis\Contracts\Repositories\Wiki\ArticleModificationRepositoryContract;
use Polis\Repositories\BaseRepositoryAbstract;
use Psr\Log\LoggerInterface as LogContract;

/**
 * Class ArticleModificationRepository
 */
class ArticleModificationRepository extends BaseRepositoryAbstract implements ArticleModificationRepositoryContract
{
    /**
     * ArticleModificationRepository constructor.
     */
    public function __construct(ArticleModification $model, LogContract $log)
    {
        parent::__construct($model, $log);
    }
}
