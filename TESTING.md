# Pomelo Fleet Testing Standards

This is the **canonical testing standard** for every Pomelo Laravel codebase:
`polis-laravel` (the shared template) and every consumer app — `client-driver`,
`HighScoresCenter`, `PolisOS`, `Card-Collecting`, `cnh-merchandising-api`.

Every repo's `CLAUDE.md` carries a condensed "Testing standards" section that
points back here. **When in doubt, this file wins.** Follow it to the letter;
do not invent a different structure, do not relax a rule "just this once."

---

## 1. Taxonomy — which suite does a test belong in?

There are exactly three suites. Picking the wrong one is a bug, even if the test
passes.

### Unit — pure logic. NO database. NO application bootstrap.

The Unit suite does **not** migrate a database and does **not** boot the Laravel
application. **A DB-touching test in the Unit suite is a bug.** If your test
needs a real row, a factory `->create()`, or a booted container, it does not
belong here — move it to Integration or Feature.

Put in Unit:

- **Service math / formulas / transforms** — pure functions: given inputs,
  assert outputs. No persistence.
- **Model relations / casts / accessors — at the DEFINITION level only.** You
  assert the *shape* of the relationship, not data through it. Instantiate the
  model with `new Model()` and inspect the relation object's keys/table. No
  rows, no DB.

**Canonical Unit example — definition-level relation test**
(`client-driver` → `tests/Athenia/Unit/Models/RoleTest.php`):

```php
final class RoleTest extends TestCase
{
    public function testUsers(): void
    {
        $role = new Role();
        $relation = $role->users();

        $this->assertEquals('role_user', $relation->getTable());
        $this->assertEquals('role_user.role_id', $relation->getQualifiedForeignPivotKeyName());
        $this->assertEquals('role_user.user_id', $relation->getQualifiedRelatedPivotKeyName());
        $this->assertEquals('roles.id', $relation->getQualifiedParentKeyName());
    }
}
```

No `->create()`, no `setupDatabase()` — it asserts the relation definition and
nothing else. That is exactly what a Unit model test looks like.

### Integration — real database interactions.

Runs against a **real DB** (migrated). Put in Integration:

- **App-level repositories — FULL coverage. Every public method gets tested.**
  No exceptions: a repository is the DB access layer and every method it exposes
  must have at least a success path plus its failure/edge paths.
- **DB-touching services** — services whose behavior depends on persisted state.
- **Model parts that need the DB** — anything you cannot assert at the
  definition level (e.g. scopes exercised against rows, computed accessors that
  read related rows).
- **Data-transform migrations** — migrations that reshape existing data.
- **Observers** — model observer side effects.

**Canonical Integration example — repository test**
(`client-driver` → `tests/Athenia/Integration/Repositories/FeatureRepositoryTest.php`):

```php
final class FeatureRepositoryTest extends TestCase
{
    use DatabaseSetupTrait, MocksApplicationLog;

    protected FeatureRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();

        $this->repository = new FeatureRepository(new Feature(), $this->getGenericLogMock());
        Feature::all()->each(fn (Feature $i) => $i->delete());
    }

    public function testFindAllSuccess(): void
    {
        Feature::factory()->count(5)->create();
        $this->assertCount(5, $this->repository->findAll());
    }

    public function testFindOrFailFails(): void
    {
        $this->expectException(ModelNotFoundException::class);
        $this->repository->findOrFail(42);
    }
    // ...every other public method of the repository, success + failure...
}
```

### Feature — ONLY the app-exposed surface.

Feature tests exercise what the outside world can actually call:

- **HTTP endpoints** (routes/controllers), and
- **Console commands.**

**Controllers and FormRequests are covered HERE — and only here.** Do **not**
write a separate Integration test for a controller or a FormRequest; the Feature
test for the endpoint is their coverage.

---

## 2. Feature-test organization (STRICT)

This is not a style preference. It is enforced structure.

### One file per endpoint

Exactly one test file per endpoint, named `<Resource><Action>Test`:

| HTTP / route          | File name                 |
| --------------------- | ------------------------- |
| `GET`    index        | `ResourceIndexTest`       |
| `GET`    show         | `ResourceShowTest`        |
| `POST`   create       | `ResourceCreateTest`      |
| `PUT` / `PATCH` update | `ResourceUpdateTest`     |
| `DELETE` destroy      | `ResourceDeleteTest`      |

- **ALL tests for an endpoint live in that ONE file.** Do not split one
  endpoint across multiple files. Do not create more than one file for an
  endpoint.
- Nest files in a directory tree that mirrors the resource / nested-resource
  path, e.g.
  `tests/.../Feature/Http/Organization/OrganizationCreateTest.php`,
  `tests/.../Feature/Http/Organization/Asset/OrganizationAssetCreateTest.php`.

### Every validation error is tested — NO EXCEPTION

For **every** endpoint, **every possible validation error must have a test.**
For each field and each rule on it:

- `required` / `present` — send it missing, assert the error.
- type — send the wrong type (string where int, etc.), assert the error.
- **For numbers, test ALL bounds** — `min`, `max`, `between`, `integer`, etc.
  **If a field validates a number, you test every numeric validation error on
  it.** One rule missing a test is a gap, and gaps are bugs.

### Validation errors return 422

Assert `422` on every validation-failure test. (Not 400.)

### Specialized cases / optimizations

Any optimization or specialized behavior of the endpoint (filters, includes,
side effects) goes in the **same** endpoint file, **each as a single test.**

**Canonical Feature example — create endpoint enumerating every validation
error** (`client-driver` →
`tests/Athenia/Feature/Http/Organization/OrganizationCreateTest.php`):

```php
final class OrganizationCreateTest extends TestCase
{
    use DatabaseSetupTrait, MocksApplicationLog, RolesTesting;

    private $route = '/v1/organizations';

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();
        $this->mockApplicationLog();
    }

    public function testNotLoggedInUserBlocked(): void
    {
        $this->json('POST', $this->route)->assertStatus(403);
    }

    public function testCreateSuccessful(): void
    {
        $this->actAs(Role::SUPER_ADMIN);
        $properties = ['name' => 'An Organization'];
        $this->json('POST', $this->route, $properties)
            ->assertStatus(201)
            ->assertJson($properties);
    }

    public function testCreateFailsMissingRequiredFields(): void
    {
        $this->actAs(Role::SUPER_ADMIN);
        $this->json('POST', $this->route)
            ->assertStatus(422)
            ->assertJson(['errors' => ['name' => ['The name field is required.']]]);
    }

    public function testCreateFailsInvalidStringFields(): void
    {
        $this->actAs(Role::SUPER_ADMIN);
        $this->json('POST', $this->route, ['name' => 5435])
            ->assertStatus(422)
            ->assertJson(['errors' => ['name' => ['The name must be a string.']]]);
    }

    public function testCreateFailsStringTooLong(): void
    {
        $this->actAs(Role::SUPER_ADMIN);
        $this->json('POST', $this->route, ['name' => str_repeat('a', 121)])
            ->assertStatus(422)
            ->assertJson(['errors' => ['name' => ['The name may not be greater than 120 characters.']]]);
    }
}
```

Note: one file, all tests for `POST /v1/organizations`, a dedicated test per
validation rule (`required`, type, max length), all returning `422`. A numeric
field would additionally get `integer`, `min`, `max`/`between` tests here.

See `OrganizationIndexTest.php` in the same directory for the index/pagination
pattern (auth gates, empty page, multi-page pagination assertions).

---

## 3. Profiling / performance tests — query-count invariance

Performance is tested as **query-count invariance over dataset size**, not raw
timing.

Rules:

- Run the endpoint/code twice against a **small two-size dataset** — e.g.
  **N = 10** and **N = 40** rows.
- Assert the **number of executed queries is identical** across the two sizes.
  Equal counts prove the code is **O(1) in queries with respect to size** (no
  N+1).
- **No huge seeds.** 10 vs 40 is enough; thousands of rows are forbidden.
- **No verbose dumps**, **no raw-timing assertions** (`microtime`, wall-clock,
  "< X ms" — all banned; they are flaky and prove nothing structural).
- The test **must fail if an N+1 is injected.** If removing an eager-load does
  not turn the test red, the test is wrong.

Sketch:

```php
public function testIndexQueryCountIsInvariantToSize(): void
{
    $this->actAs(Role::SUPER_ADMIN);

    Organization::factory()->count(10)->create();
    DB::enableQueryLog();
    $this->json('GET', '/v1/organizations')->assertStatus(200);
    $small = count(DB::getQueryLog());

    DB::flushQueryLog();
    Organization::factory()->count(30)->create(); // now 40 total
    $this->json('GET', '/v1/organizations')->assertStatus(200);
    $large = count(DB::getQueryLog());
    DB::disableQueryLog();

    $this->assertSame($small, $large, 'Query count grew with dataset size — N+1 regression.');
}
```

---

## 4. CI — every repo runs ALL suites

CI for **every** repo runs **Unit + Integration + Feature**. A repo is not green
until all three suites pass. Do not disable, skip, or conditionally exclude a
suite in CI.

(Reference: `client-driver` `apps/api/code/phpunit.xml` declares the `Unit`,
`Integration`, and `Feature` testsuites — mirrored here as `Athenia Unit`,
`Athenia Integration`, `Athenia Feature` for the ported Athenia layer.)

---

## 5. Packages (polis-laravel) — dummy consumer app harness

`polis-laravel` is a **package**, not an app, so it has no routes/controllers of
its own at runtime. Its **Feature and Integration suites run against a dummy
consumer application** (Testbench / Orchestra Testbench style) that boots the
real Polis service-provider stack the way a real consumer (e.g. the PolisOS API)
does.

This harness is the **reference** for package-level Feature/Integration testing:

- `tests/Application/` — the dummy consumer app: real `App\` Eloquent models,
  request classes, providers, routes, migrations, factories, seeders.
- `tests/Application/ApplicationTestCase.php` — base test case that boots the
  full application (the real provider stack + JWT + middleware), separate from
  the isolated Unit `TestCase`.
- `tests/bootstrap-app.php` — bootstrap for the Application (Feature/Integration)
  suites. Unlike `tests/bootstrap.php` (the isolated Unit bootstrap, which
  registers fixture stubs so Unit runs with NO consumer app on the classpath),
  this one registers the dummy app's PSR-4 namespaces and boots the real
  classes.
- `phpunit-app.xml` — the phpunit config that runs the Application-backed
  Feature/Integration suites.

The split is deliberate: the **Unit** suite exercises the package in complete
isolation (no consumer, no DB); the **Application** suites boot the dummy
consumer so abstract controllers, requests, policies, repositories and routes
are exercised the way a real app consumes them. When adding package
Feature/Integration coverage, add it against `tests/Application`, not against a
fake standalone bootstrap.
