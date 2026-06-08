# Test Fixtures

This directory holds minimal stub classes that satisfy class names referenced by
the polis-laravel source but provided by **consumer applications** at runtime.
The fixtures exist purely so the package's own (standalone Testbench) test
suite can resolve those names and mock the corresponding contracts.

## Why?

Polis contracts type-hint `App\Models\*` concrete classes in their method
signatures. For example:

```php
// src/Contracts/Repositories/Messaging/MessageRepositoryContract.php
use App\Models\Messaging\Message;
use App\Models\User\User;

public function sendEmailToUser(User $user, ..., array $via = [Message::VIA_EMAIL]): Message;
```

In a consuming Laravel application those classes are defined by the consumer.
Inside this package's own test harness they don't exist, which previously
meant:

- `Mockery::mock(MessageRepositoryContract::class)` failed because PHP could
  not resolve the `App\Models\*` type hints when generating the mock proxy.
- The `ChargeRenewal` test was reduced to reflection-only checks
  (constructor signature, signature string) — its `handle()` body could
  not be exercised.
- Several other commands / listeners / services with similar type hints sat
  in the Consumer-Only suite, undermining standalone coverage.

The fixtures unblock these tests without forcing them into the Consumer-Only
bucket.

## Layout

- `Models/` — stubs aliased to `App\Models\*` (e.g. `User`, `Subscription`,
  `PaymentMethod`).
- `Stripe/` — stubs aliased to `Cartalyst\Stripe\Exception\*`
  (`CardErrorException`, `NotFoundException`, `ApiLimitExceededException`).
  The Cartalyst Stripe package is a consumer-side dependency.
- `Vendor/` — stubs for other consumer-side dependencies that the Polis
  source touches at class-definition time (e.g. the
  `AdminUI\Laravel\EloquentJoin\Traits\EloquentJoin` trait used inside
  `Polis\Models\BaseModelAbstract`).

## Load order

All fixtures are loaded by `tests/bootstrap.php` so the `class_alias` calls
fire before any test class is autoloaded. The order is significant:

1. `Vendor/*.php` — must come first so trait aliases exist before any
   model class that uses them is loaded.
2. `Stripe/*.php` — exception stubs.
3. `Models/*.php` — domain-model stubs.

```php
foreach (glob(__DIR__.'/Fixtures/Vendor/*.php') as $f) require_once $f;
foreach (glob(__DIR__.'/Fixtures/Stripe/*.php') as $f) require_once $f;
foreach (glob(__DIR__.'/Fixtures/Models/*.php') as $f) require_once $f;
```

## Adding a new fixture

Most fixtures follow the same shape:

```php
<?php
declare(strict_types=1);

namespace Polis\Tests\Fixtures\Models;

class Article
{
    // Add #[\AllowDynamicProperties] if tests set arbitrary fields
    // directly on instances (PHP 8.2+).
}

if (! class_exists(\App\Models\Wiki\Article::class, false)) {
    class_alias(
        \Polis\Tests\Fixtures\Models\Article::class,
        \App\Models\Wiki\Article::class,
    );
}
```

Guidelines:

- **Default to a parentless class.** Inheriting `Illuminate\Database\Eloquent\Model`
  pulls in `__set` magic that interferes with Mockery dynamic-property
  assignment (the original `Mockery::mock('App\Models\User\User')` pattern in
  existing tests). Only inherit something heavier if the test actually needs
  Eloquent behaviour (e.g. `Subscription` extends `BaseModelAbstract` because
  `SubscriptionRepositoryContract::update($model)` requires it).
- **Always guard the `class_alias` with `if (! class_exists(..., false))`.**
  This prevents collisions when a Consumer-Only test (run inside PolisOS or
  another consuming app) has already autoloaded the real class via composer.
  Pass `false` as the second argument to avoid triggering the autoloader
  during the existence check.
- **Mirror class constants used as default arguments.** If the contract
  signature references e.g. `Message::VIA_EMAIL` as a default value, the
  fixture must expose the same constant.
- **No consumer-specific logic.** Fixtures exist purely to satisfy
  reflection/type-hint resolution. Behaviour-rich tests should mock the
  contracts, not rely on fixture behaviour.

## Impact

The fixture layer unblocks deeper standalone tests for code paths that
previously had to live in the Consumer-Only suite. The `ChargeRenewal`
behavioural tests in `tests/Unit/Console/Commands/ChargeRenewalTest.php`
are the first beneficiary; future PRs can migrate more
Consumer-Only tests by adding fixtures as their type hints require.
