<?php

declare(strict_types=1);

namespace Polis\Http\Core\Requests\Entity\Traits;

use Illuminate\Routing\Route;
use Polis\Contracts\Models\IsAnEntityContract;

/**
 * Class IsEntityRequestTrait
 *
 * @method Route|object|string|null route($name = null)
 */
trait IsEntityRequestTrait
{
    /**
     * Gets the entity out of the route. It will almost always be the first object.
     *
     * @return IsAnEntityContract|Route|object|string
     */
    public function getEntity(): IsAnEntityContract
    {
        $entityKey = $this->route()->parameterNames[0];

        return $this->route($entityKey);
    }
}
