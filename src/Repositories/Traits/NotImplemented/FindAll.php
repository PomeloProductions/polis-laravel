<?php

declare(strict_types=1);

namespace Polis\Repositories\Traits\NotImplemented;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Polis\Exceptions\NotImplementedException;

/**
 * Class FindAll
 */
trait FindAll
{
    /**
     * Not Implemented
     *
     * @param  int  $limit
     * @param  array  $belongsToArray  array of models this should belong to
     * @param  int  $page
     */
    public function findAll(array $filters = [], array $searches = [], array $orderBy = [], array $with = [], $limit = 10, array $belongsToArray = [], int $page = 1): LengthAwarePaginator|Collection
    {
        throw new NotImplementedException;
    }
}
