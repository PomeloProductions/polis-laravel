<?php

declare(strict_types=1);
use DG\BypassFinals;
use Illuminate\Foundation\Application;
use Polis\Tests\CustomMockInterface;

// Find and load the autoloader if not already loaded
if (! class_exists(Application::class)) {
    $autoloadPaths = [
        __DIR__.'/../../../apps/api/code/vendor/autoload.php', // from packages/ dir
        __DIR__.'/../../../../vendor/autoload.php',             // from vendor symlink
    ];

    $autoloaded = false;
    foreach ($autoloadPaths as $autoloadPath) {
        if (file_exists($autoloadPath)) {
            require $autoloadPath;
            $autoloaded = true;
            break;
        }
    }

    if (! $autoloaded) {
        throw new RuntimeException('Cannot locate autoloader. Run composer install in the app.');
    }
}

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
