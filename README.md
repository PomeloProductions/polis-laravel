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

## Naming conventions

This package uses three class-name suffixes that signal the intended
extension point. Recognising the suffix tells you whether to use a class
directly, extend it once, or treat it as a private implementation detail.

| Pattern              | Audience                | Example                                |
| -------------------- | ----------------------- | -------------------------------------- |
| `Base*Abstract`      | Internal to the package | `BaseModelAbstract`, `BasePolicyAbstract`, `BaseRepositoryAbstract`, `BaseServiceProvider` |
| `*Abstract`          | Consumer-extends        | `StatisticPolicyAbstract`, `ArticleControllerAbstract`, `BaseAssetUploadRequestAbstract`   |
| Concrete (no suffix) | Use directly            | `StatisticRepository`, `MessageMailer`, `BaseModelCacheService`                            |

* **`Base*Abstract`** — the lowest layer shared by every domain entity in
  the package. Consumers should rarely extend these directly; instead they
  extend a higher-level `*Abstract`. Treat the contract as semi-private:
  signature changes may be breaking but the class is not the recommended
  attachment point.
* **`*Abstract`** — the canonical extension surface. A consuming app
  subclasses one of these to wire its concrete `App\*` model / policy /
  controller / request into the package's flow. Method visibility is
  curated for subclassing.
* **Concrete (no suffix)** — fully instantiable and consumer-ready. Bind
  via the container, inject directly, or use as-is. No subclassing
  expected.

Whenever you add a new abstract class, pick the suffix that matches the
audience above — don't invent a new convention.

## Security model

Polis models set `protected $guarded = []` on `BaseModelAbstract`. This
makes every attribute mass-assignable on every Polis model and every
consumer subclass. The package relies on a strict layered defence rather
than per-model `$fillable` lists:

1. **FormRequest validation.** Every write path enters through a
   controller method that type-hints an `App\Http\*\Requests\*Request`
   class. The request's `rules()` method is the single source of truth
   for which attributes a client may submit and what shape they must
   take. Requests not listed in `rules()` are dropped before the
   repository sees them.
2. **Policy gates.** `Base*Abstract` policies and their `*Abstract`
   subclasses verify the authenticated user is permitted to perform the
   action, including ownership and role checks, before the repository
   is invoked.
3. **Repository contracts.** `BaseRepositoryContract::create` and
   `update` accept `array $data`; they trust the upstream pipeline. They
   are the seam where forced-value overrides (e.g. `user_id` injected
   from the authenticated session) are applied via the `$forcedValues`
   argument.

If you bypass the request layer (e.g. constructing models in a console
command, listener, or seeder), you are responsible for ensuring no
client-supplied input reaches an unsafe attribute. Prefer dispatching
through a repository with explicit `$forcedValues`, or set attributes
individually rather than passing a raw array.

## Versioning

Tagged releases follow SemVer. Breaking changes bump the major; new features
the minor; bug fixes the patch.
