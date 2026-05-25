<?php

declare(strict_types=1);

namespace Polis\Repositories\Payment;

use App\Models\Payment\PaymentMethod;
use Polis\Contracts\Repositories\Payment\PaymentMethodRepositoryContract;
use Polis\Repositories\BaseRepositoryAbstract;
use Psr\Log\LoggerInterface as LogContract;

/**
 * Class PaymentMethodRepository
 */
class PaymentMethodRepository extends BaseRepositoryAbstract implements PaymentMethodRepositoryContract
{
    /**
     * PaymentMethodRepository constructor.
     */
    public function __construct(PaymentMethod $model, LogContract $log)
    {
        parent::__construct($model, $log);
    }
}
