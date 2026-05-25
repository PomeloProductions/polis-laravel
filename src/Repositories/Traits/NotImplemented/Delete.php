<?php

declare(strict_types=1);

namespace Polis\Repositories\Traits\NotImplemented;

use Polis\Exceptions\NotImplementedException;
use Polis\Models\BaseModelAbstract;

/**
 * Class Delete
 */
trait Delete
{
    /**
     * Not implemented
     *
     * @return bool|null|void
     *
     * @throws NotImplementedException
     */
    public function delete(BaseModelAbstract $model)
    {
        throw new NotImplementedException;
    }
}
