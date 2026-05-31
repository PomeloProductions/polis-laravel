<?php

declare(strict_types=1);

namespace Polis\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Psr\Log\LoggerInterface as LogContract;

/**
 * Base test case for the polis-laravel package.
 *
 * Backed by Orchestra Testbench so the package's tests can run standalone
 * (without a consuming Laravel application).
 */
abstract class TestCase extends OrchestraTestCase
{
    /**
     * Get package providers.
     *
     * Note: polis-laravel's BaseServiceProvider references many consumer-app
     * classes (App\Models\*, App\Policies\*, etc.) that don't exist inside
     * this package. As a result we deliberately do NOT register it here;
     * tests in this CI suite exercise library code that does not require a
     * full BaseServiceProvider boot. The base providers can be registered
     * by individual tests that need them once they're refactored to not
     * depend on consumer-app classes.
     */
    protected function getPackageProviders($app): array
    {
        return [];
    }

    /**
     * This is used just temporarily often to enable debugging on the integration tests.
     */
    protected function enableDebug(): void
    {
        config(['app.debug' => true]);
    }

    /**
     * Get a logging instance that ignores anything going on
     *
     * @return LogContract
     */
    protected function getGenericLogMock()
    {
        $logMock = mock(LogContract::class);
        $logMock->shouldReceive('info');

        return $logMock;
    }
}
