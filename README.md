# polis-laravel

Shared Laravel base library for Pomelo Productions' platform apps. Provides
the foundational abstract repositories, contracts, providers, models, jobs,
listeners, observers, policies, validators, and HTTP scaffolding under the
`Polis\` PSR-4 namespace.

## Installation

```bash
composer require polis/polis-laravel:^0.2
```

The package is on Packagist as `polis/polis-laravel` — no VCS-repository
stanza is required in the consumer's `composer.json` for tagged 0.2+
releases.

## Setup

1. Create a service provider in your consumer app that extends Polis's
   abstract base provider. The base provider is abstract because each
   consumer must declare its own app-specific provider list via
   `appProviders()`:

```php
// app/Providers/AppServiceProvider.php
namespace App\Providers;

use Polis\Providers\BaseServiceProvider;

class AppServiceProvider extends BaseServiceProvider
{
    /**
     * Service providers the consumer app contributes on top of the
     * polis-laravel core bindings. Return an array of FQNs.
     *
     * @return array<int,class-string>
     */
    public function appProviders(): array
    {
        return [
            // App\Providers\AuthServiceProvider::class,
            // App\Providers\EventServiceProvider::class,
            // App\Providers\RouteServiceProvider::class,
        ];
    }

    /**
     * Hook for any consumer-specific bindings that should run after the
     * core polis-laravel register() pass.
     */
    public function registerApp(): void
    {
        // $this->app->bind(SomeContract::class, SomeImplementation::class);
    }
}
```

2. Register the provider. In a Laravel 11+ app this lives in
   `bootstrap/providers.php`:

```php
return [
    App\Providers\AppServiceProvider::class,
    // ...
];
```

3. Publish the package config (optional but recommended — without
   publishing the merged defaults still apply at runtime):

```bash
php artisan vendor:publish --tag=polis-config
```

4. Publish and run any package migrations bundled by the consuming app or
   its sibling polis packages, then run them through the standard Laravel
   migration command:

```bash
php artisan migrate
```

## Required consumer bindings

Most contracts are auto-bound via
`BaseServiceProvider::resolveConsumerOrPackage()` — the helper prefers a
consumer-side `App\…` subclass when present and falls back to the
package's `Polis\…` concrete otherwise.

The exceptions, which a consumer **must** provide before booting the app,
are:

- `App\Services\Indexing\ResourceRepositoryService` — must extend
  `Polis\Services\Indexing\BaseResourceRepositoryService` and implement
  the abstract `availableResourceRepositories()` method. Missing this
  class produces a `RuntimeException` at bind time with the FQN named
  explicitly in the message.

Consumer-app FQNs that polis-laravel type-hints in method signatures (and
therefore must be autoloadable in the consumer app, typically as Eloquent
models or HTTP requests) include — non-exhaustively —
`App\Models\User\User`, `App\Models\Asset`, `App\Models\Messaging\Message`,
`App\Models\Payment\Payment`, `App\Models\Subscription\Subscription`,
`App\Models\Vote\Ballot`, `App\Policies\…` for every policy the package
references, and `App\Http\Core\Requests\…` form-request classes. The
package's CI suite uses fixture shims under `tests/Fixtures/` to satisfy
these references in standalone runs; consumers ship the real
implementations.

## Required env / config

Add to your consumer app's `.env`:

| Variable | Required when | Purpose |
| --- | --- | --- |
| `APP_NAME` | always | Default Slack `username`; default mail `from.name` |
| `MAIL_FROM_ADDRESS` | always | Sender address on every `TemplatedMailable` |
| `MAIL_FROM_NAME` | always | Sender display name on every `TemplatedMailable` |
| `APP_DEBUG` | always | Read by exception/handler scaffolding for verbose output |
| `POLIS_PUSH_ENABLED` | push notifications | When `true`, the real `SendPushNotificationService` is bound; when `false` or unset, a no-op is bound |
| `POLIS_EMAIL_ENABLED` | email notifications | Email plumbing toggle (read by consumer-side wiring) |
| `POLIS_SLACK_ENABLED` | Slack notifications | When `true`, binds the real `SendSlackNotificationService` |
| `POLIS_SLACK_USERNAME` | Slack notifications | Slack bot identity. Defaults to `APP_NAME` |
| `POLIS_SMS_ENABLED` | SMS notifications | When `true`, binds the real `SendSMSNotificationService` (requires Twilio config) |
| `INVITATION_REQUIRED` | invitation flow | Toggles whether new sign-ups must present an invitation token |

Additional config slots read by the package (set in `config/services.php`
in the consumer app):

- `app.services.fcm.key` — FCM server key (required when
  `POLIS_PUSH_ENABLED=true`)

For Stripe-backed subscription flows, bind a consumer-specific
`StripeCustomerServiceContract` implementation. The 0.2.0 default is the
shipped `NoopStripeCustomerService` so consumers that do not use Stripe
get a working no-op without extra wiring.

## Slack notifications

The Slack `username` is read from:

```php
config('polis.slack.username', config('app.name', 'Polis'));
```

Override either by setting `POLIS_SLACK_USERNAME` in `.env` or by
publishing `config/polis.php` and editing the `slack.username` slot.

## Development

```bash
composer install
composer test
```

PHPUnit, PHPStan, and Pint are configured. Run `vendor/bin/pint` to
format, `vendor/bin/phpstan` to type-check.

The package ships two PHPUnit testsuites:

- `Unit` — standalone-runnable tests; this is what CI executes on every
  PR via Orchestra Testbench. Tests under this suite have no dependency
  on consumer-app `App\…` classes (fixture shims fill the gap).
- `Consumer-Only` — tests extracted from PolisOS that still reference
  consumer-app classes; cannot run inside this package alone. Migrated
  one-by-one to the Unit suite as fixture-backed coverage lands.

## Versioning

Tagged releases follow SemVer. Breaking changes bump the major; new
features bump the minor; bug fixes bump the patch. Release automation is
handled by `release-please`; conventional-commit prefixes drive the
version bump and changelog entry.
