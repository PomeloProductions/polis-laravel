<?php

declare(strict_types=1);

use DG\BypassFinals;
use Polis\Tests\CustomMockInterface;

require __DIR__.'/../vendor/autoload.php';

BypassFinals::enable();

if (! function_exists('mock')) {

    /**
     * Shortcut to mock an item
     *
     * @return CustomMockInterface
     */
    function mock()
    {
        Mockery::getConfiguration()->allowMockingNonExistentMethods(false);

        return call_user_func_array('Mockery::mock', func_get_args());
    }
}

if (! function_exists('callMethod')) {

    function callMethod($object, $methodName, array $arguments = [])
    {
        $class = new ReflectionClass($object);
        $method = $class->getMethod($methodName);
        $method->setAccessible(true);

        return empty($arguments) ? $method->invoke($object) : $method->invokeArgs($object, $arguments);
    }
}

if (! function_exists('getProperty')) {

    function getProperty($object, $propertyName)
    {
        $reflection = new ReflectionClass($object);
        $property = $reflection->getProperty($propertyName);
        $property->setAccessible(true);

        return $property->getValue($object);
    }
}

/*
 * Load fixture stubs.
 *
 * Polis contracts type-hint App\Models\* concrete classes (User, Subscription,
 * PaymentMethod, etc.) in their method signatures, and Polis source catches
 * Cartalyst\Stripe\Exception\* exceptions and uses an AdminUI EloquentJoin
 * trait on its base model. None of these namespaces are provided by this
 * package's composer.json — they live in the consumer application. To
 * unblock Mockery proxying of those contracts and to exercise the relevant
 * branches inside the package's own test suite, each fixture defines a
 * minimal stub and registers a class_alias under the expected FQCN. See
 * tests/Fixtures/README.md for the full pattern.
 *
 * Order matters: Vendor fixtures (e.g. the EloquentJoin trait stub) must
 * load before any Model fixture that triggers loading
 * Polis\Models\BaseModelAbstract.
 */
foreach (glob(__DIR__.'/Fixtures/Vendor/*.php') as $fixture) {
    require_once $fixture;
}
foreach (glob(__DIR__.'/Fixtures/Stripe/*.php') as $fixture) {
    require_once $fixture;
}
foreach (glob(__DIR__.'/Fixtures/JWT/*.php') as $fixture) {
    require_once $fixture;
}
foreach (glob(__DIR__.'/Fixtures/Models/*.php') as $fixture) {
    require_once $fixture;
}

/*
 * Register class aliases that point every consumer-side
 * App\Http\Core\Requests\* FQCN at a single Polis\Tests\Fixtures\Requests\StubRequest
 * subclass of BaseRequestAbstract. The controllers in
 * src/Http/Core/Controllers/* type-hint these App\* request classes; the
 * aliases make Mockery proxies of those FQCNs satisfy the runtime type
 * check without requiring a consumer app on the classpath.
 *
 * See tests/Fixtures/Requests/StubRequest.php for the full rationale.
 */
require_once __DIR__.'/Fixtures/Requests/register_aliases.php';
