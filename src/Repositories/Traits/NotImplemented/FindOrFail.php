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
     * @throws NotImplementedException
     */
    public function findOrFail(int|string $id, array $with = []): BaseModelAbstract
    {
        throw new NotImplementedException;
    }
}
