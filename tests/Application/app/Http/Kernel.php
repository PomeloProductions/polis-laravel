<?php
declare(strict_types=1);

namespace App\Http;

use Polis\Http\Middleware\ExpandParsingMiddleware;
use Polis\Http\Middleware\Issue404IfPageAfterPaginationMiddleware;
use Polis\Http\Middleware\JWTGetUserFromTokenProtectedRouteMiddleware;
use Polis\Http\Middleware\JWTGetUserFromTokenUnprotectedRouteMiddleware;
use Polis\Http\Middleware\LogMiddleware;
use Polis\Http\Middleware\SearchFilterParsingMiddleware;
use Polis\Http\Middleware\TrimStrings;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Foundation\Http\Kernel as HttpKernel;
use Illuminate\Foundation\Http\Middleware\CheckForMaintenanceMode;
use Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull;
use Illuminate\Foundation\Http\Middleware\ValidatePostSize;
use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Routing\Middleware\ThrottleRequests;

/**
 * Class Kernel
 * @package App\Http
 */
class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * These middleware are run during every request to your application.
     *
     * @var array
     */
    protected $middleware = [
        CheckForMaintenanceMode::class,
        ValidatePostSize::class,
        TrimStrings::class,
        ConvertEmptyStringsToNull::class,
        LogMiddleware::class,
        HandleCors::class,
    ];

    /**
     * The application's route middleware groups.
     *
     * @var array
     */
    protected $middlewareGroups = [
        'web' => [
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            // \Illuminate\Session\Middleware\AuthenticateSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],

        'api-v1' => [
            'throttle:120,1',
            'bindings',
            Issue404IfPageAfterPaginationMiddleware::class,
            SearchFilterParsingMiddleware::class,
            ExpandParsingMiddleware::class,
        ],
    ];

    /**
     * The application's route middleware.
     *
     * These middleware may be assigned to groups or used individually.
     *
     * @var array
     */
    protected $middlewareAliases = [
        'auth' => Authenticate::class,
        'bindings' => SubstituteBindings::class,
        'throttle' => ThrottleRequests::class,
        'jwt.auth.unprotected' => JWTGetUserFromTokenUnprotectedRouteMiddleware::class,
        'jwt.auth.protected' => JWTGetUserFromTokenProtectedRouteMiddleware::class,
    ];
}
