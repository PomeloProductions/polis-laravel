<?php

declare(strict_types=1);

namespace Polis\Http\Core\Controllers\Traits;

use Polis\Http\Core\Requests\BaseRequestAbstract;

/**
 * Trait HasViewRequests
 *
 * Adds some functionality to the controller for expand requests
 */
trait HasViewRequests
{
    /**
     * Get the expanded / width statement
     */
    protected function expand(BaseRequestAbstract $request): array
    {
        return $request->input('with', []);
    }
}
