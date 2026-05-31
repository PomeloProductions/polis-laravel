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
