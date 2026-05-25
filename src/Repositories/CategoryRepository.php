<?php

declare(strict_types=1);

namespace Polis\Repositories;

use App\Models\Category;
use Polis\Contracts\Repositories\CategoryRepositoryContract;
use Psr\Log\LoggerInterface as LogContract;

/**
 * Class CategoryRepository
 */
class CategoryRepository extends BaseRepositoryAbstract implements CategoryRepositoryContract
{
    /**
     * CategoryRepository constructor.
     */
    public function __construct(Category $model, LogContract $log)
    {
        parent::__construct($model, $log);
    }
}
