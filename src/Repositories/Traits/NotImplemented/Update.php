<?php

declare(strict_types=1);

namespace Polis\Repositories\Traits\NotImplemented;

use Polis\Exceptions\NotImplementedException;
use Polis\Models\BaseModelAbstract;

/**
 * Class Update
 */
trait Update
{
    public function update(BaseModelAbstract $model, array $data, array $forcedValues = []): BaseModelAbstract
    {
        throw new NotImplementedException;
    }
}
