<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Http\Core\Controllers;

use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Factory as ValidationFactory;
use Mockery;
use Polis\Http\Core\Requests\BaseRequestAbstract;
use Polis\Tests\TestCase;

/**
 * Shared helpers for the controller unit-test family.
 *
 * Each subclass exercises one abstract controller from
 * src/Http/Core/Controllers/* via a Polis\Tests\Fixtures\Controllers\*
 * concrete subclass. Common plumbing — a working validator binding for
 * the HasIndexRequests::limit() trait call and a helper that builds a
 * real (non-Mockery) request with merged inputs — lives here. The helper
 * returns a BaseRequestAbstract so callers can build either the shared
 * StubRequest (aliased under legacy App\ FQNs) or a concrete package
 * Polis\Http\Core\Requests\... request directly.
 */
abstract class ControllerTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->bindValidator();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Bind a Validation\Factory under the 'validator' container key so
     * Controllers using $this->validate() (via the ValidatesRequests
     * trait) can resolve it.
     */
    protected function bindValidator(): void
    {
        $loader = new ArrayLoader;
        $translator = new Translator($loader, 'en');
        $factory = new ValidationFactory($translator, app());

        app()->instance('validator', $factory);
    }

    /**
     * Build a real (non-Mockery) request of $requestFqcn carrying the
     * supplied inputs.
     *
     * Mocking a FormRequest in full means stubbing the many methods the
     * ValidatesRequests trait pokes (isPrecognitive, getValidatorInstance,
     * etc.). A real instance pre-loaded with input bags via merge() is
     * cleaner — the limit() trait helper happily validates the value
     * against its rule.
     *
     * Inputs are merged into both the parameter bag (so $request->input()
     * sees them) and as the JSON body (so $request->json()->all() returns
     * the same payload; the controllers' store/update calls rely on that
     * since they read the request body via json()).
     *
     * @param  array<string, mixed>  $inputs
     */
    protected function makeRequest(string $requestFqcn, array $inputs = []): BaseRequestAbstract
    {
        // Strip nulls so $request->input('limit', 10) returns the default
        // 10 (not (int) null = 0) when the test omits the key entirely.
        $merge = array_filter($inputs, fn ($v) => $v !== null);

        /** @var BaseRequestAbstract $request */
        $request = new $requestFqcn(
            query: $merge,
            request: $merge,
            content: json_encode($merge ?: new \stdClass) ?: '[]',
        );
        $request->headers->set('Content-Type', 'application/json');
        $request->setMethod('POST');
        $request->setContainer(app());
        $request->setRedirector(app('redirect'));

        return $request;
    }

    /**
     * Build a real index-style request with the common HasIndexRequests
     * input keys (cleaned_filter / cleaned_search / order / with / limit /
     * page) — saving each test having to spell them out.
     *
     * @param  array<string, mixed>  $inputs
     */
    protected function makeIndexRequest(string $requestFqcn, array $inputs = []): BaseRequestAbstract
    {
        return $this->makeRequest($requestFqcn, [
            'cleaned_filter' => $inputs['cleaned_filter'] ?? null,
            'cleaned_search' => $inputs['cleaned_search'] ?? null,
            'order' => $inputs['order'] ?? null,
            'with' => $inputs['with'] ?? null,
            'limit' => $inputs['limit'] ?? null,
            'page' => $inputs['page'] ?? null,
        ]);
    }
}
