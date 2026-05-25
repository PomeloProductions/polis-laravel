<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Providers;

use App\Models\User\User;
use App\Policies\User\UserPolicy;
use App\Providers\AuthServiceProvider;
use Illuminate\Auth\AuthManager;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Hashing\Hasher;
use Polis\Contracts\Repositories\User\UserRepositoryContract;
use Polis\Services\UserAuthenticationService;
use Polis\Tests\TestCase;
use Polis\Tests\Traits\ReflectionHelpers;
use ReflectionClass;

/**
 * Class AuthServiceProviderTest
 */
final class AuthServiceProviderTest extends TestCase
{
    use ReflectionHelpers;

    public function getRegisteredPolicies()
    {
        $gate = $this->app->make(Gate::class);
        $reflection = new ReflectionClass($gate);
        $property = $reflection->getProperty('policies');
        $property->setAccessible(true);

        return $property->getValue($gate);
    }

    public function test_user_authentication_registered(): void
    {
        $app = mock(Application::class);

        $app->shouldReceive('make')->once()->with(UserRepositoryContract::class)->andReturn(mock(UserRepositoryContract::class));
        $app->shouldReceive('make')->once()->with(Hasher::class)->andReturn(mock(Hasher::class));

        $auth = mock(AuthManager::class);

        $auth->shouldReceive('provider')->once()->with('user-authentication', \Mockery::on(function ($callback) use ($app) {
            $result = $callback($app, []);

            $this->assertInstanceOf(UserAuthenticationService::class, $result);

            return true;
        }));

        $app->shouldReceive('make')->once()->with('auth')->andReturn($auth);
        $app->shouldReceive('bind')->once();

        $provider = new AuthServiceProvider($app);

        $provider->boot();
    }

    public function test_guess_policy_name(): void
    {
        $provider = new AuthServiceProvider(mock(Application::class));

        $this->assertEquals(UserPolicy::class, $provider->guessPolicyName(User::class));
    }
}
