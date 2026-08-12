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
 * The {@see guessPolicyName()} method below first looks for a consumer
 * `App\Policies\...Policy` override. When that concrete does NOT exist it
 * falls back to the package's own concrete `Polis\Policies\...Policy`
 * (which extends the corresponding `...PolicyAbstract`). This means a
 * consumer application no longer has to ship an empty
 * `App\Policies\...Policy extends Polis\Policies\...PolicyAbstract {}`
 * shim: omitting it transparently uses the package default. A consumer
 * `App\Policies\...Policy` still wins whenever it is present, so existing
 * applications that DO ship those shims behave exactly as before.
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
     * Automatically guesses a policy class name based on the app structure.
     *
     * The consumer override at `App\Policies\...Policy` is always preferred.
     * When it does not exist, this falls back to the package's own concrete
     * policy at the equivalent `Polis\Policies\...Policy` FQN so consumers no
     * longer need to ship empty pass-through policy shims. Returning the
     * (possibly still-missing) `App\` name in the no-fallback case preserves
     * the original behaviour of surfacing a clear class-not-found error at the
     * first authorization check rather than silently no-op'ing.
     */
    public function guessPolicyName(string $modelClass): string
    {
        $appPolicy = str_replace('Models', 'Policies', $modelClass).'Policy';

        if (class_exists($appPolicy)) {
            return $appPolicy;
        }

        // Map the App\ policy FQN to the package concrete: App\Policies\X\YPolicy
        // becomes Polis\Policies\X\YPolicy. Only rewrite a leading `App\`
        // segment so unrelated namespaces are left untouched.
        if (str_starts_with($appPolicy, 'App\\')) {
            $polisPolicy = 'Polis\\'.substr($appPolicy, strlen('App\\'));

            if (class_exists($polisPolicy)) {
                return $polisPolicy;
            }
        }

        return $appPolicy;
    }
}
