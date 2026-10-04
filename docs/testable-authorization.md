# Fleet-wide testable authorization (403/ownership) — design

## TL;DR

The premise "controllers have no Policy and JWT no-ops so you can't assert 403"
is **already solved inside polis-laravel's own test suite** and just needs to be
**shipped to consumers**. The package has:

- a full policy layer (`Polis\Policies\*PolicyAbstract` + concretes, auto-resolved
  by `BaseAuthServiceProvider::guessPolicyName()`),
- authorization enforced in the `FormRequest` layer
  (`BaseAuthenticatedRequestAbstract::authorize()` → `Gate::authorize($action, [$model, ...])`),
- a working testable-auth base (`tests/Application/ApplicationTestCase` with
  `actAs()` / `actAsUser()`), which **already asserts 403** against real policies
  (see `tests/Feature/Http/Organization/OrganizationViewTest.php`).

**Fix:** promote the testable-auth base into the shipped `src/Testing/` namespace
so every consumer inherits `actAs()`/`actAsUser()`/role seeding against the web
guard — no per-app re-implementation, no production security change.

## Fleet reality (surveyed 2026-10-04)

The gap is **not** fleet-wide; it is one app, and the stated cause is a
misdiagnosis. Surveyed state:

| App | actAs base | Concrete policies | `markTestIncomplete` | 403 asserted? |
|-----|-----------|-------------------|----------------------|----------------|
| polis-laravel (package) | `ApplicationTestCase` | all `*PolicyAbstract` + concretes | 0 | yes |
| HighScoresCenter | own copy in `tests/TestCase` | 51 | 0 | extensive |
| PolisOS | own copy in `tests/TestCase` | 36 | 0 | extensive |
| **Card-Collecting** | own copy in `tests/TestCase` | 30 | **27** | partial |

So **Card-Collecting is the only offender**, and its 27 skips carry this comment:

> *"Pending policy: controller has no authorization check and polis-laravel
> jwt.auth.protected middleware no-ops in the `testing` environment. Re-enable
> once a Policy/Gate is wired up."*

That comment blames the JWT no-op, but HSC and PolisOS prove the no-op is not the
blocker — they assert 403 freely with the same middleware. The **actual** blocker
is that Card's endpoints never invoke authorization at all. Example
(`app/Http/Core/Controllers/User/StorageTierControllerAbstract.php`):

```php
public function store(Request $request, User $user): JsonResponse   // plain Request
{
    $data = $request->validate([ ... ]);   // inline validation, no FormRequest
    // ...no $this->authorize(), no BaseAuthenticatedRequestAbstract...
}
```

A plain `Illuminate\Http\Request` + inline `$request->validate()` means **no
policy is ever consulted**, so no 403 can ever be produced regardless of auth
state. The fix for Card is to route these endpoints through
`BaseAuthenticatedRequestAbstract` FormRequests (declaring action + model), then
un-skip the 27 tests. The middleware is correct and needs no change.

Two things therefore ship from this work:
1. a published test base so no app re-hand-rolls `actAs()` (and the gapped app has
   a canonical base), and
2. this doc as the diagnosis + the concrete Card-Collecting remediation.

---

## 1. Why JWT no-ops under `testing` (the exact mechanism)

Both JWT middlewares short-circuit on the environment name:

`src/Http/Middleware/JWTGetUserFromTokenProtectedRouteMiddleware.php`
```php
public function handle($request, \Closure $next)
{
    if ($this->app->environment() != 'testing') {
        if (! $this->auth->setRequest($request)->getToken()) {
            throw new TokenMissingException('Missing JWT Token', 400);
        }
        if (! $this->auth->authenticate()) {
            throw new TokenUserNotFoundException('JWT User Not Found', 401);
        }
    }
    return $next($request);
}
```
(`JWTGetUserFromTokenUnprotectedRouteMiddleware` has the identical
`!= 'testing'` guard around its optional-authenticate block.)

So under `APP_ENV=testing` the middleware parses **no** token and calls
`JWTAuth::authenticate()` **never**. It just forwards the request.

### Why this is actually correct (and must stay)

This no-op is not a bug to remove — it is what makes `actingAs()` work. The
authenticated user in tests is established directly on the **web guard** via
Laravel's `$this->actingAs($user)` (see `ApplicationTestCase::actAsUser()`), not
by minting and verifying a real JWT per request. If the middleware ran under
`testing`, it would either (a) demand a real bearer token on every call, or
(b) run `JWTAuth::authenticate()` which resolves/overwrites the guard user from
the (absent) token and clobber the `actingAs` user. Either way you lose the
ability to drive auth state from the test. The no-op lets the test own the guard.

**403 is still fully assertable**, because authorization happens *after* the
middleware, in the FormRequest's `authorize()` → `Gate::authorize()`:

- Not logged in → `auth()->user()` is `null` → `Gate` denies →
  `AuthorizationException` → mapped to **403** by `Polis\Exceptions\Handler`
  (`AuthorizationException => 403`).
- Logged-in-but-not-owner → policy method returns `false` → `AuthorizationException` → **403**.
- Owner/correct role → policy returns `true` → request proceeds.

Proof it already works (run from the package):
```
php -d memory_limit=1G ./vendor/bin/phpunit -c phpunit-app.xml \
  --testsuite=Feature --filter OrganizationViewTest
# OK (5 tests, 12 assertions) — includes test_not_logged_in_user_blocked => 403
#                                       and test_non_admin_users_blocked   => 403
```

> Note on 401 vs 403: because the api-v1 middleware group contains **no Laravel
> `auth` middleware** (only the JWT no-op middlewares), an unauthenticated request
> that hits a policy check produces an `AuthorizationException` (403), not an
> `AuthenticationException` (401). 401 in this stack means "a token was supplied
> but is missing/expired/invalid" (the `JWTException`/`TokenMissingException`
> family). Tests assert 403 for "not logged in" against a policy-guarded route —
> this matches the package's own `OrganizationViewTest`.

---

## 2. The policy pattern for the fleet

Already in place; documenting it so consumers follow it consistently.

### 2a. Base + abstract + concrete layering

- `Polis\Policies\BasePolicyAbstract` — `before(User $user)` grants SUPER_ADMIN
  everything, otherwise defers (`?: null`).
- `Polis\Policies\<Area>\<Model>PolicyAbstract` — per-resource abilities
  (`all`, `view`, `create`, `update`, `delete`, custom `ACTION_*`), with the real
  ownership/role logic, e.g. `UserPolicyAbstract::update()` is
  `$user->id == $model->id`.
- `Polis\Policies\<Area>\<Model>Policy` — package concrete (usually empty,
  extends the abstract).

### 2b. Resolution — consumers usually ship nothing

`BaseAuthServiceProvider::guessPolicyName()`:
1. Prefer a consumer override `App\Policies\<Area>\<Model>Policy` if it exists.
2. Otherwise rewrite `App\` → `Polis\` and fall back to the **package concrete**.
3. Otherwise return the `App\` name (so a genuinely missing policy fails loudly).

So a consumer only writes `App\Policies\...Policy` when it needs app-specific
rules; everything else inherits the package default automatically. **This is the
rule: per-resource policies live in the package; consumers override only on
divergence.**

### 2c. Enforcement — in the Request, not the controller

`BaseControllerAbstract` only uses `AuthorizesRequests` (no `authorize()` calls in
the controller bodies). Enforcement is in `BaseAuthenticatedRequestAbstract`:

```php
public function authorize(): bool
{
    $this->authorizeExpands();
    $parameters = array_merge([$this->getPolicyModel()], $this->getPolicyParameters());
    $this->authorizeRequest($this->getPolicyAction(), $parameters);  // Gate::authorize
    return true;
}
```

A concrete request (e.g. `Polis\Http\Core\Requests\User\UpdateRequest`) just
declares `getPolicyAction()`, `getPolicyModel()`, `getPolicyParameters()`. The
**pattern for any new endpoint**: extend `BaseAuthenticatedRequestAbstract`, point
it at an action + model + the route-bound model instance. Public endpoints extend
`BaseUnauthenticatedRequest` instead.

---

## 3. Make it testable in consumers — ship the test base

### The gap

`tests/Application/ApplicationTestCase` (the thing that makes 403 assertable) is
registered under `autoload-dev` only:

```
"autoload-dev": { "psr-4": { "Polis\\Tests\\": "tests/" } }
```

It is **not** in the published `autoload` (`"Polis\\": "src/"`). Consumers pull
the package via Composer, which does not install `require-dev`/`autoload-dev`, so
they never receive `ApplicationTestCase`, `actAs()`, `RolesTesting`, or the role
seeding. Each app must re-derive it; the ones that didn't end up with
`markTestIncomplete` instead of 403 assertions.

### The change (reference implementation in this PR)

Promote the reusable, app-agnostic testable-auth helpers into a **shipped**
namespace `Polis\Testing\` under `src/Testing/`:

- `Polis\Testing\AuthorizesWithGuard` (trait) — `actAs(int $roleId)`,
  `actAsUser(array $data = [])`, `$actingAs`, built on Laravel's `actingAs()`
  against the web guard. No JWT involved.
- `Polis\Testing\SeedsRoles` (trait) — seeds the canonical `Role::ROLES` rows the
  policies/factories reference.
- `Polis\Testing\RolesTesting` (trait) — `getUserOfRole()`, `rolesWithoutAdmins()`
  (promoted from `tests/Traits/RolesTesting`).

These depend only on `App\Models\User\User` + `App\Models\Role` (contracts every
consumer already provides), so they ship cleanly. The package's own
`ApplicationTestCase` is refactored to **use** these traits (single source of
truth; its behaviour is unchanged and its Feature suite still passes).

Consumers then write a thin base:

```php
abstract class TestCase extends \Tests\TestCase   // their existing Laravel base
{
    use \Polis\Testing\AuthorizesWithGuard;
    use \Polis\Testing\SeedsRoles;
    use \Polis\Testing\RolesTesting;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }
}
```

and replace every `markTestIncomplete(...)` 403 test with the real assertions:

```php
public function test_not_logged_in_blocked(): void
{
    $model = Widget::factory()->create();
    $this->json('GET', "/v1/widgets/{$model->id}")->assertStatus(403);
}

public function test_non_owner_blocked(): void
{
    $this->actAs(Role::APP_USER);                // some other user
    $model = Widget::factory()->create();         // owned by nobody / someone else
    $this->json('PATCH', "/v1/widgets/{$model->id}", [...])->assertStatus(403);
}

public function test_owner_succeeds(): void
{
    $this->actAs(Role::APP_USER);
    $model = Widget::factory()->create(['user_id' => $this->actingAs->id]);
    $this->json('PATCH', "/v1/widgets/{$model->id}", [...])->assertStatus(200);
}
```

### Why this is low-risk

- **No production code path changes.** Only `src/Testing/` (test-only helpers) is
  added; the middleware, policies, requests, handler are untouched.
- The JWT no-op stays exactly as-is — intentionally.
- The package's own `ApplicationTestCase` already proves the exact pattern works;
  we are extracting it, not inventing it.
- Consumers adopt incrementally: a consumer that doesn't update its TestCase keeps
  compiling (new traits are additive).

---

## 4. Rollout plan (consumers)

Prereq: consumers bump to the polis-laravel version that ships `src/Testing/`.

### 4a. Card-Collecting — close the actual gap (priority)

This is where the 27 `markTestIncomplete`s live. Per affected endpoint:

1. **Route it through a FormRequest**: replace the plain `Illuminate\Http\Request`
   + inline `$request->validate()` in the controller with a request class that
   extends `Polis\Http\Core\Requests\BaseAuthenticatedRequestAbstract`, declaring
   `getPolicyAction()`, `getPolicyModel()`, `getPolicyParameters()` (the
   route-bound model instance). Public reads extend `BaseUnauthenticatedRequest`.
2. **Confirm the policy covers the ability**: Card already ships 30 concrete
   policies; add/adjust the ability method (e.g. ownership `$user->id == $model->user_id`)
   where missing. Otherwise the package-concrete fallback applies.
3. **Un-skip the tests**: delete each `markTestIncomplete(...)` and assert the
   three canonical cases — `not-logged-in => 403`, `wrong-user/role => 403`,
   `owner/role => 2xx`.

### 4b. All consumers — de-duplicate on the shipped base (cleanup)

HSC, PolisOS (and Card after 4a) each carry a hand-rolled `actAs()`/`actAsUser()`
copy. Point the app's `Tests\TestCase` at the shipped traits and delete the local
copies, so there is one source of truth:

```php
use Polis\Testing\AuthorizesWithGuard;   // actAs(), actAsUser(), $actingAs
use Polis\Testing\SeedsRoles;            // seedRoles()  (call in setUp())
use Polis\Testing\RolesTesting;          // rolesWithoutAdmins(), getUserOfRole()
```

Additive and behaviour-identical to the copies they replace, so no test changes
are forced.

### 4c. New endpoints (fleet standard going forward)

Every mutating/ownership endpoint's FormRequest extends
`BaseAuthenticatedRequestAbstract`; public endpoints extend
`BaseUnauthenticatedRequest`. Per fleet test standards, one Feature file per
endpoint (`<Resource><Action>Test`) adds the authz tier (the three canonical
tests) alongside the already-required validation-error coverage. These run in the
existing Feature suite (already a hard CI gate) — no workflow change beyond the
version bump.

Suggested order: land this polis-laravel PR → cut a release → **Card-Collecting
(4a) first** (it is the only app with the real gap) → then the 4b de-dup sweep
across HSC / PolisOS / Card at leisure.

---

## 5. Recommendation

Ship the reference implementation in polis-laravel now: it is test-only,
additive, zero production-code change, and the pattern is already proven by the
package's own green Feature suite. Consumers then have a copy-paste base and a
three-test template to burn down every `markTestIncomplete`.
