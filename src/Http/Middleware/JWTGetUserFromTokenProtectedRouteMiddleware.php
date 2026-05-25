<?php

declare(strict_types=1);

namespace Polis\Http\Middleware;

use Illuminate\Foundation\Application;
use PHPOpenSourceSaver\JWTAuth\JWTAuth;
use Polis\Exceptions\JWT\TokenMissingException;
use Polis\Exceptions\JWT\TokenUserNotFoundException;

/**
 * Class JWTGetUserFromTokenProtectedRouteMiddleware
 */
class JWTGetUserFromTokenProtectedRouteMiddleware
{
    /**
     * @var JWTAuth
     */
    protected $auth;

    /**
     * @var Application
     */
    protected $app;

    /**
     * Create a new middleware instance.
     */
    public function __construct(Application $application, JWTAuth $auth)
    {
        $this->app = $application;
        $this->auth = $auth;
    }

    /**
     * Handle incoming request
     *
     * @return mixed
     *
     * @throws TokenMissingException
     * @throws TokenUserNotFoundException
     */
    public function handle($request, \Closure $next)
    {
        if ($this->app->environment() != 'testing') {
            if (! $this->auth->setRequest($request)->getToken()) {
                throw new TokenMissingException('Missing JWT Token', 400);
            }

            if (! $this->auth->authenticate()) {
                throw new TokenUserNotFoundException('JWT User Not Found', 401);
            }
        }

        return $next($request);
    }
}
