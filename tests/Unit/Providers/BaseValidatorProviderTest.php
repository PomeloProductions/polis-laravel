<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Providers;

use Illuminate\Contracts\Validation\Factory;
use Mockery;
use Polis\Providers\BaseValidatorProvider;
use Polis\Tests\TestCase;

/**
 * Exercises BaseValidatorProvider::boot() — its only job is to register
 * the package's first-party validator rules on the validator factory and
 * then defer extension hooks to the consumer via registerValidators().
 */
final class BaseValidatorProviderTest extends TestCase
{
    public function test_boot_extends_validator_factory_with_each_package_validator(): void
    {
        $factory = Mockery::mock(Factory::class);
        $factory->shouldReceive('extend')->with('token_is_not_expired', Mockery::any())->once();
        $factory->shouldReceive('extend')->with('user_owns_token', Mockery::any())->once();
        $factory->shouldReceive('extend')->with('not_present', Mockery::any())->once();
        $factory->shouldReceive('extend')->with('invitation_token_is_valid', Mockery::any())->once();
        $factory->shouldReceive('extend')->with('membership_plan_rate_is_active', Mockery::any())->once();
        $factory->shouldReceive('extend')->with('owned_by', Mockery::any())->once();
        $factory->shouldReceive('extend')->with('payment_method_is_owned_by_entity', Mockery::any())->once();
        $factory->shouldReceive('extend')->with('selected_iteration_belongs_to_article', Mockery::any())->once();

        $this->app->instance(Factory::class, $factory);

        $registered = false;
        $provider = new class($this->app, $registered) extends BaseValidatorProvider
        {
            public function __construct($app, public bool &$registered)
            {
                parent::__construct($app);
            }

            public function registerValidators(Factory $validatorFactory): void
            {
                $this->registered = true;
            }
        };

        $provider->boot();

        $this->assertTrue($registered);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
