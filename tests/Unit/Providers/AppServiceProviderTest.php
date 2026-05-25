<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Providers;

use App\Providers\AppServiceProvider;
use Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider;
use Illuminate\Foundation\Application;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Polis\Tests\TestCase;

/**
 * Class AppServiceProviderTest
 */
final class AppServiceProviderTest extends TestCase
{
    #[DataProvider('allProviders')]
    public function test_binds($provide): void
    {
        $this->app->make($provide);
    }

    public function test_provides_all(): void
    {
        $app = new Application;
        $repositoryProvider = new AppServiceProvider($app);

        $provides = $repositoryProvider->provides();
        $contracts = array_reduce($this->allProviders(), function ($carry, $item) {
            $carry[] = $item[0];

            return $carry;
        }, []);

        $misconfigured = array_values(array_diff(array_merge($provides, $contracts), array_intersect($provides, $contracts)));

        $this->assertEmpty($misconfigured, 'The following services are misconfigured '.json_encode($misconfigured));
    }

    /**
     * this gets all the repository contracts, and returns them - so we can test making them
     */
    public static function allProviders(): array
    {
        $app = new Application;
        $app['env'] = 'testing';
        $repositoryProvider = new AppServiceProvider($app);
        $repositoryProvider->register();

        $repositoryContracts = [];

        foreach (array_keys($app->getBindings()) as $contract) {
            if (Str::contains($contract, 'Contracts\Services')) {
                $repositoryContracts[] = [$contract];
            }
        }

        return $repositoryContracts;
    }

    public function test_register_environment_specific_providers(): void
    {
        $appMock = mock(Application::class);
        $appMock->shouldReceive('environment')->once()->andReturn('local');

        $appMock->shouldReceive('register')->with(IdeHelperServiceProvider::class);

        $provider = new AppServiceProvider($appMock);
        $provider->registerEnvironmentSpecificProviders();
    }
}
