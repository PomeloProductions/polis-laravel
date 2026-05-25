<?php

declare(strict_types=1);

namespace Polis\Repositories\Collection;

use App\Models\Collection\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Polis\Contracts\Repositories\Collection\CollectionItemRepositoryContract;
use Polis\Contracts\Repositories\Collection\CollectionRepositoryContract;
use Polis\Models\BaseModelAbstract;
use Polis\Repositories\BaseRepositoryAbstract;
use Polis\Traits\CanGetAndUnset;
use Psr\Log\LoggerInterface as LogContract;

class CollectionRepository extends BaseRepositoryAbstract implements CollectionRepositoryContract
{
    use CanGetAndUnset;

    public function __construct(Collection $model, LogContract $log,
        private CollectionItemRepositoryContract $collectionItemRepository)
    {
        parent::__construct($model, $log);
    }

    public function update(BaseModelAbstract $model, array $data, array $forcedValues = []): BaseModelAbstract
    {
        $newOrder = $this->getAndUnset($data, 'collection_item_order');

        /** @var Collection $updated */
        $updated = parent::update($model, $data, $forcedValues);

        if (is_array($newOrder)) {
            foreach ($newOrder as $index => $id) {
                try {
                    $collectionItem = $this->collectionItemRepository->findOrFail($id);
                    $this->collectionItemRepository->update($collectionItem, [
                        'order' => $index,
                    ]);
                } catch (ModelNotFoundException $e) {
                }
            }

            // Load this so that it is returned in reqeusts
            $updated->collectionItems;
        }

        return $updated;
    }
}
