<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Providers;

use Polis\Providers\BaseAuthServiceProvider;
use Polis\Tests\TestCase;

/**
 * Exercises the policy-class-name guesser on BaseAuthServiceProvider.
 * The boot() method is not exercised because it touches the auth manager,
 * Gate facade, and consumer-app contracts; the guesser is pure string
 * manipulation.
 */
final class BaseAuthServiceProviderTest extends TestCase
{
    public function test_guess_policy_name_substitutes_models_to_policies_and_appends_policy_suffix(): void
    {
        $provider = $this->makeProvider();

        $this->assertSame(
            'App\\Policies\\User\\UserPolicy',
            $provider->guessPolicyName('App\\Models\\User\\User'),
        );
    }

    public function test_guess_policy_name_with_nested_model_namespace(): void
    {
        $provider = $this->makeProvider();

        $this->assertSame(
            'App\\Policies\\Wiki\\ArticlePolicy',
            $provider->guessPolicyName('App\\Models\\Wiki\\Article'),
        );
    }

    public function test_guess_policy_name_with_flat_namespace(): void
    {
        $provider = $this->makeProvider();

        $this->assertSame(
            'App\\Policies\\AssetPolicy',
            $provider->guessPolicyName('App\\Models\\Asset'),
        );
    }

    public function test_guess_policy_name_does_not_replace_when_models_segment_absent(): void
    {
        $provider = $this->makeProvider();

        // When the model class doesn't contain 'Models', str_replace
        // leaves it alone and just appends "Policy".
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
