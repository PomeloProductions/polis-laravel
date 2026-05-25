<?php

declare(strict_types=1);

namespace Polis\Contracts\Http;

use Illuminate\Routing\Route;
use Polis\Contracts\Models\IsAnEntityContract;

/**
 * Interface IsEntityRequestContract
 */
interface HasEntityInRequestContract
{
    /**
     * Gets the entity out of the route. It will almost always be the first object.
     *
     * @return IsAnEntityContract|Route|object|string
     */
    public function getEntity(): IsAnEntityContract;
}
