<?php

declare(strict_types=1);

namespace Polis\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Class Issue404IfPageAfterPaginationMiddleware
 */
class Issue404IfPageAfterPaginationMiddleware
{
    /**
     * Handle an incoming request, add 404 if page > last_page
     *
     * @param  Request  $request
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $response = $next($request);
        if ($page = $request->input('page')) {
            $responseData = json_decode($response->getContent());
            if (! empty($responseData->last_page)) {
                if ($responseData->last_page < $page) {
                    $response->setStatusCode(404);
                }
            }
        }

        return $response;
    }
}
