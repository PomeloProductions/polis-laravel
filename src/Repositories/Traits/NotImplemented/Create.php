<?php

declare(strict_types=1);

namespace Polis\Repositories\Traits\NotImplemented;

use Polis\Exceptions\NotImplementedException;
use Polis\Models\BaseModelAbstract;

/**
 * Class Create
 */
trait Create
{
    public function create(array $data = [], ?BaseModelAbstract $parentModel = null, array $forcedData = []): BaseModelAbstract
    {
        throw new NotImplementedException;
    }
}
