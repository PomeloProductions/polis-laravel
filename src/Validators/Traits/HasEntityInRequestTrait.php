<?php

declare(strict_types=1);

namespace Polis\Validators\Traits;

use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Polis\Contracts\Models\IsAnEntityContract;

/**
 * Class IsEntityRequestTrait
 *
 * @property Request $request
 */
trait HasEntityInRequestTrait
{
    /**
     * Gets the entity out of the route. It will almost always be the first object.
     *
     * @return IsAnEntityContract|Route|object|string
     */
    public function getEntity(): IsAnEntityContract
    {
        $entityKey = $this->request->route()->parameterNames()[0];

        return $this->request->route($entityKey);
    }
}
