<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Exceptions;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Polis\Exceptions\Handler;
use Polis\Exceptions\ValidationException;
use Polis\Tests\TestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class HandlerTest
 */
final class HandlerTest extends TestCase
{
    public function test_debug_true_has_trace_info_in_response(): void
    {
        config(['app.debug' => true]);
        $handler = new Handler($this->app);

        $request = new Request;
        $request->headers->set('Accept', 'application/json');
        $exception = new ValidationException;

        $responseJson = $handler->render($request, $exception)->content();
        $this->assertStringContainsString('trace', $responseJson);
    }

    public function test_debug_false_no_trace_info_in_response(): void
    {
        $handler = new Handler($this->app);

        $request = new Request;
        $request->headers->set('Accept', 'application/json');
        $exception = new ValidationException;

        $responseJson = $handler->render($request, $exception)->content();
        $this->assertStringNotContainsString('trace', $responseJson);
    }

    public function test_message_set_special_for_not_found_http_exception(): void
    {
        $handler = new Handler($this->app);

        $request = new Request(server: [
            'REQUEST_URI' => '/v1/status/hi',
        ]);
        $request->headers->set('Accept', 'application/json');
        $exception = new NotFoundHttpException;

        $responseJson = $handler->render($request, $exception)->content();
        $this->assertJsonStringEqualsJsonString(json_encode(['message' => 'This path was not found.']), $responseJson);
    }

    public function test_model_not_found_displays_custom_message(): void
    {
        $handler = new Handler($this->app);

        $request = new Request(server: [
            'REQUEST_URI' => '/v1/user/42534',
        ]);
        $request->headers->set('Accept', 'application/json');
        $exception = new ModelNotFoundException;

        $responseJson = $handler->render($request, $exception)->content();
        $this->assertJsonStringEqualsJsonString(json_encode(['message' => 'This item was not found.']), $responseJson);
    }
}
