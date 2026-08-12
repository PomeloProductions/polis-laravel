<?php

declare(strict_types=1);

use DG\BypassFinals;
use Polis\Tests\CustomMockInterface;

/*
 * Bootstrap for the Application (Feature/Integration) test suites.
 *
 * Unlike tests/bootstrap.php (used by the standalone Unit suite), this
 * bootstrap deliberately does NOT register the fixture class_alias stubs for
 * App\Models\*, App\Http\Core\Requests\* or the AdminUI EloquentJoin trait.
 * Those stubs exist so the Unit suite can run WITHOUT a consumer application
 * on the classpath. The Application suites, by contrast, boot the real dummy
 * consumer app shipped under tests/Application (real App\ Eloquent models,
 * real request classes) against the real adminui/laravel-eloquent-joins
 * trait — exactly the way the PolisOS API consumes this package. Registering
 * the stubs here would shadow those real classes and break the boot.
 */

$loader = require __DIR__.'/../vendor/autoload.php';

/*
 * Register the dummy consumer application's namespaces for the Application
 * (Feature/Integration) suites ONLY.
 *
 * These are intentionally NOT in composer.json's autoload-dev: a global psr-4
 * mapping would make the real App\ consumer classes autoloadable during the
 * standalone Unit suite too, which must exercise the Polis package in complete
 * isolation (its resolver/fallback tests assume no consumer overrides exist).
 * Registering them here scopes the consumer app to the suites that need it.
 */
$loader->addPsr4('App\\', __DIR__.'/Application/app/');
$loader->addPsr4('Database\\Factories\\', __DIR__.'/Application/database/factories/');
$loader->addPsr4('Database\\Seeders\\', __DIR__.'/Application/database/seeders/');

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
