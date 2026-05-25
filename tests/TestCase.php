<?php

declare(strict_types=1);

namespace Polis\Tests;

use App\Models\User\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\WithoutEvents;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Psr\Log\LoggerInterface as LogContract;

abstract class TestCase extends BaseTestCase
{
    /**
     * @var User
     */
    protected $actingAs;

    /**
     * Creates the application.
     *
     * @return Application
     */
    public function createApplication()
    {
        $app = require $this->getBasePath().'/bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        return $app;
    }

    /**
     * Get the base path to the Laravel application.
     * Override this in app-level TestCase if needed.
     */
    protected function getBasePath(): string
    {
        // Allow explicit override via environment
        if ($path = env('APP_BASE_PATH')) {
            return $path;
        }

        // Common locations to search
        $candidates = [
            __DIR__.'/../../../..',                    // vendor symlink: vendor/polis/polis-laravel/tests/../../..
            __DIR__.'/../../../apps/api/code',         // packages dir: packages/polis-laravel/tests/../../../apps/api/code
            '/var/www/html/code',                        // Docker container
        ];

        foreach ($candidates as $candidate) {
            if (file_exists($candidate.'/bootstrap/app.php')) {
                return realpath($candidate);
            }
        }

        throw new \RuntimeException('Cannot locate Laravel application bootstrap. Set APP_BASE_PATH env var.');
    }

    /**
     * Boot the testing helper traits.
     *
     * @return void
     */
    protected function setUpTraits()
    {
        $uses = array_flip(class_uses_recursive(static::class));

        if (isset($uses[WithoutMiddleware::class])) {
            $this->disableMiddlewareForAllTests();
        }

        if (isset($uses[WithoutEvents::class])) {
            $this->disableEventsForAllTests();
        }
    }

    /**
     * This is used just temporarily often to enable debugging on the integration tests.
     */
    protected function enableDebug()
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

    /**
     * Call this to make the user an authenticated user
     *
     * @param  array  $data
     */
    protected function actAsUser($data = [])
    {
        $this->actingAs = User::factory()->create($data);
        $this->actingAs($this->actingAs);
    }

    /**
     * Act as a role type
     */
    protected function actAs(int $roleId)
    {
        $this->actAsUser();
        $this->actingAs->addRole($roleId);
    }
}
