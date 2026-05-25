# polis-laravel

Shared Laravel base library for Pomelo Productions' platform apps. Provides
the foundational abstract repositories, contracts, providers, models, jobs,
listeners, observers, policies, validators, and HTTP scaffolding under the
`Polis\` PSR-4 namespace.

## Consuming

This package is consumed via Composer's Git VCS repository type. Add to the
consuming app's `composer.json`:

```json
{
  "repositories": [
    {
      "type": "vcs",
      "url": "git@github.com:PomeloProductions/polis-laravel.git"
    }
  ],
  "require": {
    "polis/polis-laravel": "^0.1"
  }
}
```

Then `composer require polis/polis-laravel`.

## Development

```bash
composer install
composer test
```

PHPUnit, PHPStan, and Pint are configured. Run `vendor/bin/pint` to format,
`vendor/bin/phpstan` to type-check.

## Versioning

Tagged releases follow SemVer. Breaking changes bump the major; new features
the minor; bug fixes the patch.
