<?php

declare(strict_types=1);

namespace Polis\Repositories\Payment;

use App\Models\Payment\Payment;
use Polis\Contracts\Repositories\Payment\LineItemRepositoryContract;
use Polis\Contracts\Repositories\Payment\PaymentRepositoryContract;
use Polis\Models\BaseModelAbstract;
use Polis\Repositories\BaseRepositoryAbstract;
use Polis\Traits\CanGetAndUnset;
use Psr\Log\LoggerInterface as LogContract;

/**
 * Class PaymentRepository
 */
class PaymentRepository extends BaseRepositoryAbstract implements PaymentRepositoryContract
{
    use CanGetAndUnset;

    /**
     * @var LineItemRepositoryContract
     */
    private $lineItemRepository;

    /**
     * PaymentRepository constructor.
     */
    public function __construct(Payment $model, LogContract $log,
        LineItemRepositoryContract $lineItemRepository)
    {
        parent::__construct($model, $log);
        $this->lineItemRepository = $lineItemRepository;
    }

    /**
     * Overrides the parent in order to sync the line items
     *
     * @return BaseModelAbstract
     */
    public function create(array $data = [], ?BaseModelAbstract $relatedModel = null, array $forcedValues = []): BaseModelAbstract
    {
        $lineItems = $this->getAndUnset($data, 'line_items', []);
        $model = parent::create($data, $relatedModel, $forcedValues);

        $this->syncChildModels($this->lineItemRepository, $model, $lineItems);

        return $model;
    }

    /**
     * Overrides the parent in order to sync the line items
     */
    public function update(BaseModelAbstract $model, array $data, array $forcedValues = []): BaseModelAbstract
    {
        $lineItems = $this->getAndUnset($data, 'line_items', null);
        /** @var Payment $updated */
        $updated = parent::update($model, $data, $forcedValues);

        if ($lineItems) {
            $this->syncChildModels($this->lineItemRepository, $model, $lineItems, $updated->lineItems);
        }

        return $updated;
    }
}
