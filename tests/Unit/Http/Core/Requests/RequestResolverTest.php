<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Http\Core\Requests;

use Illuminate\Routing\ControllerDispatcher;
use Illuminate\Routing\Route;
use Polis\Http\Core\Requests\Feature\IndexRequest as PolisFeatureIndexRequest;
use Polis\Http\Core\Requests\Probe\BoundRequest;
use Polis\Http\Core\Requests\Probe\InjectRequest;
use Polis\Http\Core\Requests\RequestResolver;
use Polis\Tests\TestCase;

/*
 * NOTE ON FIXTURE FQNs
 * --------------------
 * tests/bootstrap.php globally class_alias()es every real
 * `App\Http\Core\Requests\...` FQN to a StubRequest so the *old* App-typed
 * controllers could be exercised. That means class_exists() is TRUE for those
 * FQNs inside this suite. To test the genuine "consumer shipped NO override"
 * path we therefore use synthetic `...\Probe\...` request namespaces that the
 * bootstrap does not alias, so class_exists('App\\...\\Probe\\...') is really
 * false unless a test defines it.
 */

/**
 * Proves the request-resolution mechanism that lets consumers drop empty
 * `App\Http\Core\Requests\...` shims.
 *
 * The controllers type-hint the package's own `Polis\Http\Core\Requests\...`
 * request. {@see RequestResolver} then:
 *   - leaves the package request as-is when the consumer ships no override
 *     (Laravel builds the package concrete directly — no shim needed), and
 *   - rebinds the package request FQN to the consumer's
 *     `App\Http\Core\Requests\...` override when it exists, so existing
 *     consumers keep getting their own request injected.
 *
 * The end-to-end assertions use Laravel's real ControllerDispatcher so the
 * route dependency resolver (the thing that historically required the App
 * class to exist for the type-hint) is exercised for real.
 */
final class RequestResolverTest extends TestCase
{
    public function test_app_override_for_maps_polis_request_to_app_fqn(): void
    {
        $this->assertSame(
            'App\\Http\\Core\\Requests\\Feature\\IndexRequest',
            RequestResolver::appOverrideFor(PolisFeatureIndexRequest::class),
        );
    }

    public function test_app_override_for_returns_null_for_non_package_requests(): void
    {
        $this->assertNull(RequestResolver::appOverrideFor(\stdClass::class));
        $this->assertNull(RequestResolver::appOverrideFor('App\\Models\\User'));
    }

    public function test_resolve_returns_package_request_when_no_override_exists(): void
    {
        // Use a synthetic package request under an un-aliased Probe namespace so
        // class_exists('App\\...\\Probe\\NoOverrideRequest') is genuinely false.
        $polisFqn = 'Polis\\Http\\Core\\Requests\\Probe\\NoOverrideRequest';
        if (! class_exists($polisFqn, false)) {
            eval('namespace Polis\\Http\\Core\\Requests\\Probe; class NoOverrideRequest extends \\Polis\\Http\\Core\\Requests\\Feature\\IndexRequest {}');
        }

        // No App override exists, so resolve() returns the package concrete —
        // i.e. the consumer needs no shim.
        $this->assertSame($polisFqn, RequestResolver::resolve($polisFqn));
    }

    public function test_resolve_prefers_app_override_when_it_exists(): void
    {
        // Synthesize a consumer override at the FQN resolve() would look for.
        $overrideFqn = 'App\\Http\\Core\\Requests\\Probe\\OverrideRequest';
        $polisFqn = 'Polis\\Http\\Core\\Requests\\Probe\\OverrideRequest';

        if (! class_exists($overrideFqn, false)) {
            eval('namespace App\\Http\\Core\\Requests\\Probe; class OverrideRequest {}');
        }

        $this->assertSame($overrideFqn, RequestResolver::resolve($polisFqn));
    }

    public function test_no_binding_registered_when_consumer_has_no_override(): void
    {
        $polisFqn = 'Polis\\Http\\Core\\Requests\\Probe\\UnboundRequest';
        if (! class_exists($polisFqn, false)) {
            eval('namespace Polis\\Http\\Core\\Requests\\Probe; class UnboundRequest extends \\Polis\\Http\\Core\\Requests\\Feature\\IndexRequest {}');
        }

        RequestResolver::registerBindings($this->app, [$polisFqn]);

        // Nothing was bound for the package request (no App override exists), so
        // the container still builds the package concrete directly.
        $this->assertInstanceOf($polisFqn, $this->app->make($polisFqn));
    }

    public function test_controller_method_injection_uses_package_request_without_a_shim(): void
    {
        // The controller type-hints a package request with no App override and
        // no binding. Laravel's ControllerDispatcher resolves and injects the
        // package concrete straight into the action — the case that used to
        // require an App shim just to satisfy the type-hint.
        $polisFqn = 'Polis\\Http\\Core\\Requests\\Probe\\InjectRequest';
        if (! class_exists($polisFqn, false)) {
            eval('namespace Polis\\Http\\Core\\Requests\\Probe; class InjectRequest extends \\Polis\\Http\\Core\\Requests\\Feature\\IndexRequest {}');
        }

        $controller = new class
        {
            public ?object $received = null;

            public function index(InjectRequest $request): string
            {
                $this->received = $request;

                return 'ok';
            }
        };

        $result = $this->dispatch($controller);

        $this->assertSame('ok', $result);
        $this->assertInstanceOf($polisFqn, $controller->received);
    }

    public function test_controller_method_injection_uses_app_override_when_bound(): void
    {
        // A consumer override that extends the package request (exactly like the
        // empty shims do today).
        $polisFqn = 'Polis\\Http\\Core\\Requests\\Probe\\BoundRequest';
        $overrideFqn = 'App\\Http\\Core\\Requests\\Probe\\BoundRequest';

        if (! class_exists($polisFqn, false)) {
            eval('namespace Polis\\Http\\Core\\Requests\\Probe; class BoundRequest extends \\Polis\\Http\\Core\\Requests\\Feature\\IndexRequest {}');
        }
        if (! class_exists($overrideFqn, false)) {
            eval('namespace App\\Http\\Core\\Requests\\Probe; class BoundRequest extends \\Polis\\Http\\Core\\Requests\\Probe\\BoundRequest {}');
        }

        RequestResolver::registerBindings($this->app, [$polisFqn]);

        $controller = new class
        {
            public ?object $received = null;

            public function index(BoundRequest $request): string
            {
                $this->received = $request;

                return 'ok';
            }
        };

        $this->dispatch($controller);

        // The consumer override (a subclass of the package request) is injected,
        // proving existing consumers keep getting their own request.
        $this->assertInstanceOf($overrideFqn, $controller->received);
    }

    /**
     * Dispatch $controller->index() through Laravel's real ControllerDispatcher
     * so the route dependency resolver performs the method-injection.
     */
    private function dispatch(object $controller): mixed
    {
        $route = new Route(['GET'], '/probe', ['uses' => static fn () => null]);
        $route->bind($this->app->make('request'));

        return (new ControllerDispatcher($this->app))->dispatch($route, $controller, 'index');
    }
}
