<?php

declare(strict_types=1);

namespace Polis\Contracts\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Interface HasLocationDataRepositoryContract
 */
interface HasLocationRepositoryContract
{
    /**
     * Finds all requests around a specific location
     *
     * @param  float  $radius  in KM
     * @param  int  $limit
     */
    public function findAllAroundLocation(float $latitude, float $longitude, float $radius, array $filters = [], array $searches = [], array $orderBy = [], array $with = [], $limit = 10, array $belongsToArray = [], int $page = 1): LengthAwarePaginator;
}
