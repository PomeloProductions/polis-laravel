<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Providers;

use App\Providers\AppRepositoryProvider;
use Illuminate\Foundation\Application;
use Illuminate\Support\Str;
use Polis\Tests\TestCase;

/**
 * Class AppRepositoryProviderTest
 */
final class AppRepositoryProviderTest extends TestCase
{
    public function allProviders()
    {
        $app = new Application;
        $repositoryProvider = new AppRepositoryProvider($app);
        $repositoryProvider->register();

        $repositoryContracts = [];

        foreach (array_keys($app->getBindings()) as $contract) {
            if (Str::contains($contract, 'Contracts\Repositories')) {
                $repositoryContracts[] = [$contract];
            }
        }

        return $repositoryContracts;
    }

    public function test_provides_all(): void
    {
        $app = new Application;
        $repositoryProvider = new AppRepositoryProvider($app);

        $provides = $repositoryProvider->provides();
        $contracts = array_reduce($this->allProviders(), function ($carry, $item) {
            $carry[] = $item[0];

            return $carry;
        }, []);

        $this->assertEquals(0, count(array_diff(array_merge($provides, $contracts), array_intersect($provides, $contracts))));
    }
}
