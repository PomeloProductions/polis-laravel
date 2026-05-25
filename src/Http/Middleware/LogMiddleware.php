<?php

declare(strict_types=1);

namespace Polis\Http\Middleware;

use Closure;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class LogMiddleware
 */
class LogMiddleware
{
    /**
     * @var LoggerInterface
     */
    protected $apiLog;

    /**
     * @var Application
     */
    protected $app;

    /**
     * LogMiddleware constructor.
     */
    public function __construct(Application $application, LoggerInterface $apiLog)
    {
        $this->app = $application;
        $this->apiLog = $apiLog;
    }

    /**
     * Just pass through
     *
     * @param  Request  $request
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        return $next($request);
    }

    /**
     * On Terminate, log the request and response
     */
    public function terminate(Request $request, Response $response)
    {
        if ($this->app->environment() != 'testing') { // @todo is there a better, more 'secure' way to do this??

            $this->apiLog->info('V1', [
                'request' => [
                    'method' => $request->method(),
                    'url' => $request->fullUrl(),
                    'data' => $request->all(),
                    'headers' => $request->headers->all(),
                    'ip' => $request->ip(),
                ],
                'response' => [
                    'status' => $response->getStatusCode(),
                    'headers' => $response->headers->all(),
                    'content' => $response->getContent(),
                ],
            ]);
        }
    }
}
