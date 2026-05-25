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
    /**
     * @return BaseModelAbstract|void
     */
    public function create(array $data = [], ?BaseModelAbstract $parentModel = null, array $forcedData = [])
    {
        throw new NotImplementedException;
    }
}
