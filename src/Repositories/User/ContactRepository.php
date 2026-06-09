<?php

declare(strict_types=1);

namespace Polis\Repositories\User;

use App\Models\User\Contact;
use App\Models\User\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Polis\Contracts\Repositories\User\ContactRepositoryContract;
use Polis\Repositories\BaseRepositoryAbstract;
use Psr\Log\LoggerInterface as LogContract;

/**
 * Class ContactRepository
 */
class ContactRepository extends BaseRepositoryAbstract implements ContactRepositoryContract
{
    /**
     * ContactRepository constructor.
     */
    public function __construct(Contact $model, LogContract $log)
    {
        parent::__construct($model, $log);
    }

    /**
     * @param  int  $limit
     * @return LengthAwarePaginator|Collection
     */
    public function findAll(array $filter = [], array $search = [], array $orderBy = [], array $with = [], $limit = 10, array $belongsToArray = [], int $pageNumber = 1): LengthAwarePaginator|Collection
    {
        $query = parent::buildFindAllQuery($filter, $search, $orderBy, $with, []);

        /** @var User $user */
        foreach ($belongsToArray as $user) {
            $query->where('initiated_by_id', $user->id);
            $query->orWhere('requested_id', $user->id);
        }

        return $query->paginate($limit, $columns = ['*'], $pageName = 'page', $pageNumber);
    }
}
