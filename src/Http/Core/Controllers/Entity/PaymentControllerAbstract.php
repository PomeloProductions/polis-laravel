<?php

declare(strict_types=1);

namespace Polis\Http\Core\Controllers\Entity;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Polis\Contracts\Models\IsAnEntityContract;
use Polis\Contracts\Repositories\Payment\PaymentRepositoryContract;
use Polis\Http\Core\Controllers\BaseControllerAbstract;
use Polis\Http\Core\Controllers\Traits\HasIndexRequests;
use Polis\Http\Core\Requests;

/**
 * Class PaymentControllerAbstract
 */
abstract class PaymentControllerAbstract extends BaseControllerAbstract
{
    use HasIndexRequests;

    private PaymentRepositoryContract $repository;

    /**
     * PaymentControllerAbstract constructor.
     */
    public function __construct(PaymentRepositoryContract $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @return LengthAwarePaginator|Collection
     */
    public function index(Requests\Entity\Payment\IndexRequest $request, IsAnEntityContract $entity)
    {
        $filter = $this->filter($request);

        $filter[] = [
            'owner_id',
            '=',
            $entity->id,
        ];
        $filter[] = [
            'owner_type',
            '=',
            $entity->morphRelationName(),
        ];

        return $this->repository->findAll($filter, $this->search($request), $this->order($request), $this->expand($request), $this->limit($request), [], (int) $request->input('page', 1));
    }
}
