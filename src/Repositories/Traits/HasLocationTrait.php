<?php

declare(strict_types=1);

namespace Polis\Repositories\Traits;

use AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Polis\Models\BaseModelAbstract;

/**
 * Class HasLocationTrait
 *
 * @property BaseModelAbstract $model
 *
 * @method EloquentJoinBuilder buildFindAllQuery(array $filters = [], array $searches = [], array $orderBy = [], array $with = [], array $belongsToArray = [])
 */
trait HasLocationTrait
{
    /**
     * Applies the location radius query mathematics onto the query
     *
     * @param  float  $radius  in KM
     * @param  EloquentJoinBuilder  $query
     * @return EloquentJoinBuilder
     */
    public function applyGeoQuery(float $latitude, float $longitude, float $radius, $query)
    {
        $distanceFormula = "( 6371 * acos( cos( radians($latitude) ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians($longitude) ) + sin( radians($latitude) ) * sin(radians(latitude)) ) )";
        $query->whereRaw("$distanceFormula < $radius");
        $query->orderByRaw($distanceFormula);

        $query->groupBy($this->model->getTable().'.id');

        return $query;
    }

    /**
     * Finds all requests around a specific location
     *
     * @param  float  $radius  in KM
     * @param  int  $limit
     */
    public function findAllAroundLocation(float $latitude, float $longitude, float $radius, array $filters = [], array $searches = [], array $orderBy = [], array $with = [], $limit = 10, array $belongsToArray = [], int $page = 1): LengthAwarePaginator
    {
        $query = $this->buildFindAllQuery($filters, $searches, $orderBy, $with, $belongsToArray);

        $query = $this->applyGeoQuery($latitude, $longitude, $radius, $query);

        return $query->paginate($limit, $columns = ['*'], $pageName = 'page', $page);
    }
}
