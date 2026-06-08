<?php

declare(strict_types=1);

/**
 * Fixture stubs for Cartalyst\Stripe\Exception\* exceptions.
 *
 * The cartalyst/stripe-laravel package is a consumer-side dependency and
 * is not installed in this package's composer.json. The Polis source
 * references three of its exception classes by name (catch blocks in
 * Console\Commands\ChargeRenewal and Exceptions\Handler). To exercise
 * those catch branches inside this package's standalone Testbench
 * harness we define minimal stand-ins that extend RuntimeException and
 * register them under the Cartalyst namespace via class_alias.
 *
 * Loaded automatically by tests/bootstrap.php alongside the App\Models\*
 * fixtures.
 */

namespace Polis\Tests\Fixtures\Stripe;

use RuntimeException;

class CardErrorException extends RuntimeException {}
class NotFoundException extends RuntimeException {}
class ApiLimitExceededException extends RuntimeException {}

if (! class_exists(\Cartalyst\Stripe\Exception\CardErrorException::class, false)) {
    class_alias(
        CardErrorException::class,
        \Cartalyst\Stripe\Exception\CardErrorException::class,
    );
}

if (! class_exists(\Cartalyst\Stripe\Exception\NotFoundException::class, false)) {
    class_alias(
        NotFoundException::class,
        \Cartalyst\Stripe\Exception\NotFoundException::class,
    );
}

if (! class_exists(\Cartalyst\Stripe\Exception\ApiLimitExceededException::class, false)) {
    class_alias(
        ApiLimitExceededException::class,
        \Cartalyst\Stripe\Exception\ApiLimitExceededException::class,
    );
}
