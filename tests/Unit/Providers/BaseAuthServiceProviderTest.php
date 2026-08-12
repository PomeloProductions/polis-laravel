<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Providers;

use Polis\Policies\AssetPolicy;
use Polis\Policies\User\UserPolicy;
use Polis\Policies\Wiki\ArticlePolicy;
use Polis\Providers\BaseAuthServiceProvider;
use Polis\Tests\TestCase;

/**
 * Exercises the policy-class-name guesser on BaseAuthServiceProvider.
 * The boot() method is not exercised because it touches the auth manager,
 * Gate facade, and consumer-app contracts; the guesser is pure string
 * manipulation plus class-existence resolution.
 *
 * Resolution contract (see {@see BaseAuthServiceProvider::guessPolicyName()}):
 *   1. Prefer the consumer override `App\Policies\...Policy` when it exists.
 *   2. Otherwise fall back to the package concrete `Polis\Policies\...Policy`
 *      when that exists (so consumers can drop empty policy shims).
 *   3. Otherwise return the `App\...Policy` name verbatim, so a genuinely
 *      missing policy surfaces a clear class-not-found error.
 *
 * Inside the package test suite no `App\...` classes are autoloadable, so
 * every model whose Polis concrete policy ships with the package resolves to
 * the `Polis\` fallback here. That is exactly the "consumer omitted the shim"
 * scenario, proven without leaking an `App\` namespace into composer.json.
 */
final class BaseAuthServiceProviderTest extends TestCase
{
    public function test_falls_back_to_polis_concrete_when_no_app_policy_exists(): void
    {
        $provider = $this->makeProvider();

        // App\Policies\User\UserPolicy does not exist in the package suite, but
        // Polis\Policies\User\UserPolicy (a concrete shipped by the package)
        // does — so the guesser returns the package fallback.
        $this->assertSame(
            UserPolicy::class,
            $provider->guessPolicyName('App\\Models\\User\\User'),
        );
    }

    public function test_falls_back_to_polis_concrete_for_nested_model_namespace(): void
    {
        $provider = $this->makeProvider();

        $this->assertSame(
            ArticlePolicy::class,
            $provider->guessPolicyName('App\\Models\\Wiki\\Article'),
        );
    }

    public function test_falls_back_to_polis_concrete_for_flat_namespace(): void
    {
        $provider = $this->makeProvider();

        $this->assertSame(
            AssetPolicy::class,
            $provider->guessPolicyName('App\\Models\\Asset'),
        );
    }

    public function test_prefers_app_policy_when_consumer_override_exists(): void
    {
        $provider = $this->makeProvider();

        // Define a synthetic consumer override at the FQN the guesser would
        // produce for a fictional App\Models\Widget model. Because the class
        // now exists, the guesser must return the App\ override rather than
        // any package fallback — this is the backward-compat guarantee for
        // consumers that DO ship their own policies.
        $appPolicyFqn = 'App\\Policies\\WidgetPolicy';
        if (! class_exists($appPolicyFqn, false)) {
            eval('namespace App\\Policies; class WidgetPolicy {}');
        }

        $this->assertSame(
            $appPolicyFqn,
            $provider->guessPolicyName('App\\Models\\Widget'),
        );
    }

    public function test_returns_app_name_verbatim_when_neither_app_nor_polis_policy_exists(): void
    {
        $provider = $this->makeProvider();

        // No App\Policies\...Policy and no Polis\Policies\...Policy exists for
        // this model, so the guesser returns the App\ name so the gate throws
        // a clear class-not-found error instead of silently no-op'ing.
        $this->assertSame(
            'App\\Policies\\Nonexistent\\ThingPolicy',
            $provider->guessPolicyName('App\\Models\\Nonexistent\\Thing'),
        );
    }

    public function test_does_not_rewrite_non_app_namespaces(): void
    {
        $provider = $this->makeProvider();

        // A model outside the App\ root is never rewritten to Polis\; the
        // guesser just appends the Policy suffix as before.
        $this->assertSame(
            'Foo\\BarPolicy',
            $provider->guessPolicyName('Foo\\Bar'),
        );
    }

    private function makeProvider(): BaseAuthServiceProvider
    {
        // BaseAuthServiceProvider is abstract — anonymous subclass for tests.
        return new class($this->app) extends BaseAuthServiceProvider {};
    }
}
