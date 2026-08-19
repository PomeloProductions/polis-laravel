# Coverage Baseline Report

Measured across **all three** test suites — `Unit` (from `phpunit.xml`,
Orchestra Testbench, no consumer-app classes) plus `Feature` and
`Integration` (from `phpunit-app.xml`, booted against the dummy consumer app
under `tests/Application`). Every suite instruments the same source scope:
`./src`.

The previous edition of this file reported only the standalone `Unit` suite
(and a stale 11% headline). That badly understated real coverage: the
Feature/Integration suites exercise the HTTP controllers, repositories,
policies and DB-backed services that the Unit suite cannot reach standalone.
This edition reports each suite and the **combined** (union) figure, which is
the honest measure of how much of the package the whole corpus touches.

## Headline numbers

| Suite                   | Tests | Assertions | Line coverage        |
| ----------------------- | ----- | ---------- | -------------------- |
| Unit                    | 1146  | 2547       | 3280 / 7274 (45.09%) |
| Feature                 | 678   | 1796       | 3481 / 7270 (47.88%) |
| Integration             | 536   | 902        | 1609 / 7270 (22.13%) |
| **Combined (union)**    | 2360  | 5245       | **4816 / 7259 (66.35%)** |

Combined line coverage is the union of covered lines across all three clover
reports: a `./src` line counts as covered if **any** suite hit it.

At the file level (source scope `./src`): **461** source files, **382** with
at least one covered line, **301** fully covered.

The Unit figure jumped from ~35.94% to **45.09%** with the data-driven
validation matrix (`tests/Unit/Validation/ValidationMatrixTest.php`), which
instantiates every rule-bearing model and drives Laravel's validator against
each typed rule.

## CI wiring

- `pcov` is requested via `shivammathur/setup-php` in
  `.github/workflows/tests.yml`.
- The Unit step emits `coverage-unit.xml`; the Feature and Integration steps
  now emit `coverage-feature.xml` and `coverage-integration.xml`.
- All three clovers (plus `coverage-combined.json`) are uploaded as a single
  `coverage-<php>` artifact.
- Two gates run on the PHP 8.4 job:
  - **Unit floor** — `tools/check-coverage-threshold.php coverage-unit.xml`
    fails the job if Unit line coverage drops below its floor.
  - **Combined floor** — `tools/merge-coverage.php --min 64.0 …` unions the
    three reports and fails the job if combined line coverage drops below its
    floor. This is what stops Feature/Integration coverage from silently
    regressing — the Unit-only gate cannot see it.

## Thresholds

| Gate     | Floor  | Real baseline | Tool                                  |
| -------- | ------ | ------------- | ------------------------------------- |
| Unit     | 44.5%  | 45.09%        | `tools/check-coverage-threshold.php`  |
| Combined | 64.0%  | 66.35%        | `tools/merge-coverage.php --min`      |

Each floor sits ~1–2 pts below its measured baseline: close enough to trip on
a real regression, loose enough to absorb normal run-to-run fluctuation. Raise
both as more tests land.

## What the combined suite does NOT reach (the ~34% gap)

The uncovered slice is concentrated in code paths that need live external
integrations or fixtures neither suite provides:

- Stripe billing paths (`Polis\Services\Stripe*`, `Console\Commands\
  ChargeRenewal` body, most Payment/Subscription listeners) — need the
  Cartalyst Stripe gateway.
- Slack / push-notification delivery services — need live transports.
- Some deep controller error branches and rarely-hit repository query
  builders.

These are the natural next targets for raising the floor.

## Real bugs surfaced

None. The validation matrix confirmed every typed rule already behaves to
spec; no source changes were required to make the matrix green.

<!-- 0.5.0: HTTP validation-error status changed 400 -> 422 (breaking). See PR #37. -->
