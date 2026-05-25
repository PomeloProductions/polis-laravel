<?php

declare(strict_types=1);

namespace Polis\Observers;

use Polis\Contracts\Models\CanBeIndexedContract;
use Polis\Contracts\Repositories\ResourceRepositoryContract;

/**
 * Class IndexableModelObserver
 */
class IndexableModelObserver
{
    /**
     * @var ResourceRepositoryContract
     */
    private $resourceRepository;

    /**
     * IndexableModelObserver constructor.
     */
    public function __construct(ResourceRepositoryContract $resourceRepository)
    {
        $this->resourceRepository = $resourceRepository;
    }

    /**
     * Handle the CanBeIndexedContract "created" event.
     *
     * @return void
     */
    public function created(CanBeIndexedContract $model)
    {
        $this->indexModel($model);
    }

    /**
     * Handle the CanBeIndexedContract "updated" event.
     *
     * @return void
     */
    public function updated(CanBeIndexedContract $model)
    {
        $this->indexModel($model);
    }

    /**
     * Creates an index of the model
     */
    private function indexModel(CanBeIndexedContract $model)
    {
        $content = $model->getContentString();
        if ($content) {
            $data = [
                'content' => $content,
                'resource_id' => $model->id,
                'resource_type' => $model->morphRelationName(),
            ];

            if ($model->resource) {
                $this->resourceRepository->update($model->resource, $data);
            } else {
                $this->resourceRepository->create($data);
            }
        }
    }

    /**
     * Handle the CanBeIndexedContract "deleted" event.
     *
     * @return void
     */
    public function deleted(CanBeIndexedContract $event)
    {
        if ($event->resource) {
            $this->resourceRepository->delete($event->resource);
        }
    }
}
