<?php

declare(strict_types=1);

namespace Polis\Http\Core\Controllers\Traits;

use Illuminate\Foundation\Validation\ValidatesRequests;
use Polis\Http\Core\Requests\BaseRequestAbstract;

/**
 * Trait HasIndexRequests
 *
 * Adds some functionality to a controller for dealing with index requests
 */
trait HasIndexRequests
{
    use HasViewRequests, ValidatesRequests;

    /**
     * Get the search statement
     */
    protected function filter(BaseRequestAbstract $request): array
    {
        return $request->input('cleaned_filter', []);
    }

    /**
     * Get the search statement
     */
    protected function search(BaseRequestAbstract $request): array
    {
        return $request->input('cleaned_search', []);
    }

    /**
     * Get the order passed in by the user
     */
    protected function order(BaseRequestAbstract $request): array
    {
        return $request->input('order', []);
    }

    /**
     * Validate and get the limit for pagination per page
     */
    protected function limit(BaseRequestAbstract $request): int
    {
        $this->validate($request, [
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        return (int) $request->input('limit', 10);
    }
}
