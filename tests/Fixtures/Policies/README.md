# Fixture Policies

Polis's policy classes are abstract (`*PolicyAbstract`). Consumer apps
extend them in their own `App\Policies\*` namespace to register against
Laravel's gate. To exercise the abstract gate logic in standalone tests
without pulling in any `App\*` code, this directory mirrors the
`src/Policies/**` tree with empty concrete subclasses.

Each concrete fixture extends the matching abstract with an empty body.
Tests instantiate these concrete policies and invoke the inherited gate
methods (view / update / delete / create / etc.) using mocked
`App\Models\*` fixtures registered via `class_alias` in
`tests/Fixtures/Models/`.

This pattern keeps the package free of any phantom "production" policy
class while still measuring abstract-policy coverage.
