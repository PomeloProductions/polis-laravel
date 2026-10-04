# CLAUDE.md

Guidance for Claude Code when working in this repository.

## Testing standards

This repo is the **home of the canonical Pomelo fleet testing standard** —
see **[`TESTING.md`](./TESTING.md)** at the repo root for the full standard with
examples. It is authoritative for this repo and every consumer app. Key rules:

**Three suites — pick the right one (wrong suite = bug, even if it passes):**

- **Unit** — pure logic. NO database, NO app bootstrap. Service math / formulas /
  transforms, and model relations/casts/accessors tested at the **definition
  level** (`new Model()`, assert the relation's keys/table — no rows). The Unit
  suite does not migrate; **a DB-touching test in Unit is a bug.**
- **Integration** — real DB. App-level repositories get **FULL coverage (every
  public method, success + failure)**; DB-touching services; model parts needing
  the DB; data-transform migrations; observers.
- **Feature** — ONLY the app-exposed surface: **HTTP endpoints + console
  commands**. Controllers and FormRequests are covered **here and only here**.

**Feature-test organization (STRICT):**

- **One file per endpoint**, named `<Resource><Action>Test`: index →
  `ResourceIndexTest`, show → `ResourceShowTest`, create → `ResourceCreateTest`,
  update (PUT/PATCH) → `ResourceUpdateTest`, delete → `ResourceDeleteTest`. ALL
  tests for that endpoint live in that ONE file — never split, never duplicate.
- **Every possible validation error is tested — NO EXCEPTION, every endpoint.**
  Each rule: `required`/`present`, type, and for numbers **ALL** bounds
  (`min`/`max`/`between`/`integer`/…).
- Validation errors return **422**.
- Optimizations / specialized cases → same endpoint file, each a single test.

**Profiling / perf tests:** query-count **invariance** over a **small two-size
dataset** (e.g. N=10 vs N=40) — identical count proves O(1) w.r.t. size. NO huge
seeds, NO verbose dumps, NO raw-timing assertions. Must fail when an N+1 is
injected.

**CI:** every suite runs — **Unit + Integration + Feature**.

**Packages (this repo):** because `polis-laravel` is a package, its **Feature and
Integration suites run against a dummy consumer application** (Orchestra
Testbench style). That harness is the reference for all package-level
Feature/Integration testing:

- `tests/Application/` — the dummy consumer app (real `App\` models, requests,
  providers, routes, migrations, factories).
- `tests/Application/ApplicationTestCase.php` — boots the full real provider
  stack (JWT + middleware), separate from the isolated Unit `TestCase`.
- `tests/bootstrap-app.php` — Application-suite bootstrap (registers the dummy
  app's PSR-4 + real classes), distinct from the isolated Unit `tests/bootstrap.php`.
- `phpunit-app.xml` — phpunit config that runs the Application-backed suites.

Add package Feature/Integration coverage against `tests/Application`, never a
fake standalone bootstrap.
