<?php

declare(strict_types=1);

namespace Polis\Repositories\Traits\NotImplemented;

use Polis\Exceptions\NotImplementedException;
use Polis\Models\BaseModelAbstract;

/**
 * Class FindOrFail
 */
trait FindOrFail
{
    /**
     * Not Implemented
     *
     * @return BaseModelAbstract|void
     *
     * @throws NotImplementedException
     */
    public function findOrFail($id, array $with = [])
    {
        throw new NotImplementedException;
    }
}
