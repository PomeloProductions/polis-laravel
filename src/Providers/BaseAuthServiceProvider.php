<?php

declare(strict_types=1);

namespace Polis\Providers;

use Illuminate\Auth\AuthManager;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Polis\Contracts\Repositories\User\UserRepositoryContract;
use Polis\Contracts\ThreadSecurity\ThreadSubjectGateProviderContract;
use Polis\Services\UserAuthenticationService;
use Polis\ThreadSecurity\ThreadSubjectGateProvider;

/**
 * Class AuthServiceProvider
 *
 * Auto-bind behaviour
 * -------------------
 * This provider does NOT auto-bind policies. Every package policy
 * (`Polis\Policies\*PolicyAbstract`) is abstract by design — consumer
 * applications must enumerate the abilities and authorization logic that
 * apply to their own model classes. As a result the consumer MUST provide
 * concrete policy classes at:
 *
 *   App\Policies\<...>\<Model>Policy
 *
 * extending the corresponding `Polis\Policies\<...>\<Model>PolicyAbstract`.
 * Policy bodies can be empty one-liners if the abstract already covers the
 * needed abilities.
 *
 * The {@see guessPolicyName()} method below tells Laravel's gate to look
 * for `App\Policies\...Policy` (NOT `Polis\Policies\...Policy`), so failing
 * to ship those concretes will surface a "class not found" error from the
 * gate at first authorisation check rather than silently no-op.
 */
abstract class BaseAuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     * Normally these will be automatically guessed as long as
     *  the models directory structure matches the policies directory structure.
     * Any exceptions should be set here.
     *
     * @var array
     */
    protected $policies = [
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        $this->app->bind(ThreadSubjectGateProviderContract::class, function () {
            return new ThreadSubjectGateProvider($this->app);
        });

        /** @var AuthManager $auth */
        $auth = $this->app->make('auth');

        $auth->provider('user-authentication', function ($app, array $config) {

            /** @var Application $app */
            $userRepository = $app->make(UserRepositoryContract::class);
            $hasher = $app->make(Hasher::class);

            return new UserAuthenticationService($hasher, $userRepository);
        });

        /** @var Gate $gate */
        Gate::guessPolicyNamesUsing([$this, 'guessPolicyName']);
    }

    /**
     * Automatically guesses a policies name based on the app structure
     */
    public function guessPolicyName(string $modelClass): string
    {
        return str_replace('Models', 'Policies', $modelClass).'Policy';
    }
}
