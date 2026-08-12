<?php

declare(strict_types=1);

namespace Polis\Exceptions;

use Cartalyst\Stripe\Exception\CardErrorException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException;
use Polis\Exceptions\JWT\TokenMissingException;
use Polis\Exceptions\JWT\TokenUserNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

/**
 * Class Handler
 */
class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        AuthenticationException::class,
        AuthorizationException::class,
        HttpException::class,
        ModelNotFoundException::class,
        TokenMismatchException::class,
        ValidationException::class,
        CardErrorException::class,
    ];

    /**
     * Render an exception into an HTTP response.
     *
     * @param  Request  $request
     * @return Response
     */
    public function render($request, Throwable $exception)
    {
        // There is probably a better way to do this, but this is quick and dirty for now
        if (Str::startsWith($request->getPathInfo(), '/v')) {
            [$status, $response] = $this->parseException($exception);

            // if we're in debug mode, add extra information for us
            if (config('app.debug')) {
                $response['exception_class'] = get_class($exception);
                $response['exception_message'] = $exception->getMessage();
                $response['exception_trace'] = $exception->getTrace();
            }

            // Return a JSON response with the response array and status code
            return response()->json($response, $status);
        }

        return parent::render($request, $exception);
    }

    /**
     * Parse the exception for the status code and the message
     */
    protected function parseException(Throwable $exception): array
    {
        $status = 500;
        $response = [
            'message' => 'Sorry, something went wrong.',
        ];

        switch (true) {
            case $exception instanceof TokenExpiredException:
            case $exception instanceof TokenInvalidException:
                // Expired/invalid JWTs are an authentication failure, not a
                // server error. These are subclasses of JWTException and would
                // already fall through to the generic JWTException case below,
                // but we map them explicitly so the 401 contract is regression
                // proof against future reordering of the switch.
                $response['message'] = $exception->getMessage();
                $status = 401;
                break;

            case $exception instanceof JWTException:
                $response['message'] = $exception->getMessage();
                $status = 401;
                break;

            case $exception instanceof TokenMissingException:
            case $exception instanceof TokenUserNotFoundException:
                $response['message'] = $exception->getMessage();
                $status = $exception->getCode();
                break;

            case $exception instanceof AuthorizationException:
                $status = 403;
                $response['details'] = $exception->getMessage();
                break;

            case $exception instanceof HttpException:
                $response['message'] = $exception instanceof NotFoundHttpException ?
                    'This path was not found.' : $exception->getMessage();
                $status = $exception->getStatusCode();
                break;

            case $exception instanceof ValidationException:
                $response['errors'] = $exception->errors();
                $status = 400;
                break;

            case $exception instanceof ModelNotFoundException:
                $response['message'] = 'This item was not found.';
                $status = 404;
                break;
        }

        return [$status, $response];
    }
}
