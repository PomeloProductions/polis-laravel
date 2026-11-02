# Multi-dimensional intersection statistics

This document describes the public API surface of the multi-dimensional
generalization of the `Statistic → TargetStatistic` primitive. The design was
owner-approved; see the PR description for rationale.

A statistic can now compute a scalar **measure** over an arbitrary set of
**axes** (dimensions) in ONE SQL `GROUP BY`, materializing one sparse
`TargetStatistic` **cell** per non-empty group. Existing single-target and
`unique`-filter statistics are untouched: with no `dimensions`/`measure`
declared they keep running through the legacy in-PHP path and are stored as
cells with `dimension_key = ''`.

## Declaring a multi-dimensional statistic

A statistic opts into the GROUP BY engine by declaring `dimensions` + `measure`.

```php
Statistic::create([
    'name'  => 'comparisons_by_type',
    'model' => 'platform_comparison', // morph alias of the MEASURE'S BASE TABLE
    'relation' => '',                 // unused by the engine
    'kind'  => 'count',               // 'count' (default) or 'derived'

    // The EXTRA axes (the primary axis is the morph target below).
    'dimensions' => [
        ['name' => 'voting_type', 'kind' => 'scalar', 'column' => 'voting_type'],
    ],

    // The scalar aggregate + the PRIMARY-axis binding (which column is the
    // target id and which morph alias to stamp as target_type).
    'measure' => [
        'agg'    => 'count',          // count | count_distinct | sum | max
        // 'column' => '...',         // required for count_distinct|sum|max
        'target' => ['type' => 'user', 'id_column' => 'owner_id'],
    ],
]);
```

Recompute with the engine (full or bounded per target):

```php
app(MultiDimensionalStatisticService::class)->recompute($statistic);
// or, incrementally, for a single primary target:
app(MultiDimensionalStatisticService::class)
    ->recomputeForTarget($statistic, 'user', $ownerId);
```

### Measure (`agg`)

| `agg`            | needs `column` | SQL                                   |
|------------------|----------------|---------------------------------------|
| `count`          | no             | `COUNT(*)`                            |
| `count_distinct` | yes            | `COUNT(DISTINCT base.<column>)` — the count-once grain; fan-out joins don't inflate |
| `sum`            | yes            | `SUM(base.<column>)`                  |
| `max`            | yes            | `MAX(base.<column>)`                  |

### Axis (`dimension`) kinds

- **scalar** — an enum/id/string column: `{name, kind: 'scalar', column}`.
- **entity** — a morph reference: `{name, kind: 'entity', id_column, type}` (or
  `type_column` to resolve the morph type per row).
- **date_bucket** — a first-class time axis, bucketed in a configured timezone
  with a configurable week start:
  `{name, kind: 'date_bucket', column, granularity: day|week|month|year,
  timezone: 'America/New_York', week_start: 0}` (`week_start`: 0=Sunday .. 6=Saturday).
  Bucketing happens in SQL (driver-aware: SQLite + MySQL) so `count_distinct`
  stays correct. Resolving in the configured tz is what fixes downstream
  midnight-drift.

### Declarative joins (`joins`)

By default the engine counts ONE base table with no joins. Declaring a `joins`
list lets a statistic COUNT one base table while its **axes, static filters,
measure, and primary target reference RELATED tables** — so you no longer need a
bespoke per-shape SQL VIEW to flatten the data first.

```php
'joins' => [
    [
        'table' => 'release_dates',   // the related table to join
        'alias' => 'rd',              // its alias (defaults to the table name)
        'left'  => 'base.id',         // qualified `alias.column`
        'right' => 'rd.release_id',   // `base` is the reserved base alias
        'type'  => 'inner',           // inner | left ; default inner
        'guard_soft_deletes' => true, // default true: exclude deleted_at rows
        'on_extra' => [               // optional extra AND-ed ON conditions
            ['ci.item_type', '=', 'release'],
        ],
    ],
],
```

- **Ordering.** Joins apply in declaration order, so a later join may reference
  an earlier join's alias (`release_dates` → `collection_items` → `collections`).
- **Qualifying columns.** EVERY column reference in the definition is run through
  a `qualify()` step: a bare name (`platform_id`) is prefixed with the `base`
  alias; an already-qualified `alias.column` (`rd.release_date`, `c.owner_id`,
  `cic.category_id`) is used as-is. A statistic that declares **no joins** has
  only bare references, so the engine behaves exactly as before (back-compat).
  This applies to the dimension column / entity id_column / type_column, the
  date_bucket column, pair-axis columns, the measure column
  (`count_distinct`/`sum`/`max`), the primary-target `id_column`, and static
  filter `field`s.
- **Soft-delete guard per join** (default on; auto-skipped when the joined table
  has no `deleted_at`):
  - `inner` → `alias.deleted_at IS NULL` is added to the WHERE clause.
  - `left`  → it is added to the ON closure, so a LEFT join is NOT silently
    narrowed into an inner join by excluding the unmatched (all-NULL) rows.
- **`on_extra`.** A list of `[column, operator, value]` triples AND-ed into the
  ON clause (e.g. the `collection_items.item_type = 'release'` morph guard).
- **Fan-out.** A one-to-many join multiplies base rows; use a
  `count_distinct` measure over the base id to keep counts correct (see below).
- **Sub-select joins.** Instead of `table` a join may carry a `table_raw` raw-SQL
  sub-select (aliased derived table) — see *Sub-select joins (`table_raw`)* below.

#### Example: HSC "releases per year per platform" (no owner)

`releases` has no date column — a release's (regional) dates live in a
one-to-many `release_dates` table. Count DISTINCT releases, grouped by platform
and by a year bucket off the JOINED date:

```php
Statistic::create([
    'name'  => 'releases_per_year_per_platform',
    'model' => 'release',             // base table = releases
    'relation' => '',
    'kind'  => 'count',
    'dimensions' => [
        ['name' => 'platform', 'kind' => 'scalar', 'column' => 'platform_id'],
        ['name' => 'year', 'kind' => 'date_bucket', 'column' => 'rd.release_date',
            'granularity' => 'year', 'timezone' => 'UTC'],
    ],
    'joins' => [
        ['table' => 'release_dates', 'alias' => 'rd',
            'left' => 'base.id', 'right' => 'rd.release_id'],
    ],
    // count DISTINCT releases so a release with multiple regional dates in a
    // year is counted ONCE (the fan-out does not inflate "Total Releases").
    'measure' => ['agg' => 'count_distinct', 'column' => 'base.id'],
]);
```

#### Sub-select joins (`table_raw`)

Instead of a physical `table`, a join may carry a **`table_raw`** — a raw SQL
sub-select (WITHOUT the surrounding parens or alias) that the engine wraps as
`(<table_raw>) as <alias>` and joins to. This lets a statistic join to an
**aliased derived table**: derive a per-parent column (e.g. a MIN/MAX-per-parent
aggregate) and then group by it, without a physical view.

```php
'joins' => [
    [
        // Derive ONE earliest_year per release from its many regional dates.
        'table_raw' => 'SELECT release_id, YEAR(MIN(release_date)) AS earliest_year '
            .'FROM release_dates '
            .'WHERE release_date IS NOT NULL AND deleted_at IS NULL '
            .'GROUP BY release_id',
        'alias' => 'ey',          // REQUIRED for a table_raw join (no table name to fall back to)
        'left'  => 'base.id',     // join the derived table to the base release
        'right' => 'ey.release_id',
        'type'  => 'inner',       // inner | left ; default inner
    ],
],
```

Rules and semantics:

- **Exactly one** of `table` / `table_raw` must be present (validated — both or
  neither throws). `alias` is **required** for a `table_raw` join; `left` / `right`
  / `type` / `on_extra` behave exactly as for a physical-table join, and the
  derived column (`ey.earliest_year`) is referenced like any other
  `alias.column` in axes / filters / measure / target.
- **No automatic soft-delete guard.** The engine applies NO `deleted_at IS NULL`
  and does NO `deleted_at` column lookup for a `table_raw` join — the alias is
  not a physical table, and the sub-select is responsible for its own filtering
  (note the `deleted_at IS NULL` *inside* the sub-select's own `WHERE` above).
- **⚠ SECURITY — raw SQL.** `table_raw` is spliced directly into the query with
  **no escaping or parameterization**. It is for **TRUSTED, system-SEEDED
  statistic configuration ONLY** and must **never** contain or be built from user
  input. Treat it like a migration string, not a request parameter.

##### Example: HSC "earliest year" (MIN release date per release)

Bucket each release by the **earliest** of its many regional release dates, then
count releases per earliest year. A grouped sub-select derives one
`earliest_year` per release; the engine groups by that derived column:

```php
Statistic::create([
    'name'  => 'releases_by_earliest_year',
    'model' => 'release',             // base table = releases
    'relation' => '',
    'kind'  => 'count',
    'dimensions' => [
        ['name' => 'earliest_year', 'kind' => 'scalar', 'column' => 'ey.earliest_year'],
    ],
    'joins' => [
        ['table_raw' => 'SELECT release_id, YEAR(MIN(release_date)) AS earliest_year '
            .'FROM release_dates WHERE release_date IS NOT NULL AND deleted_at IS NULL '
            .'GROUP BY release_id',
            'alias' => 'ey', 'left' => 'base.id', 'right' => 'ey.release_id'],
    ],
    // count DISTINCT releases (the sub-select already collapses to one row per
    // release, so each release contributes to exactly one earliest-year bucket).
    'measure' => ['agg' => 'count_distinct', 'column' => 'base.id'],
]);
```

#### Example: category variant (owner on a joined table)

"Bucket List / Finished per year per platform" additionally joins
`collection_items` → `collection_item_categories` → `collections`, binds the
primary target owner to the JOINED `collections.owner_id`, and filters by
category:

```php
'joins' => [
    ['table' => 'collection_items', 'alias' => 'ci',
        'left' => 'base.id', 'right' => 'ci.item_id',
        'on_extra' => [['ci.item_type', '=', 'release']]],
    ['table' => 'collection_item_categories', 'alias' => 'cic',
        'left' => 'ci.id', 'right' => 'cic.collection_item_id'],
    ['table' => 'collections', 'alias' => 'c',
        'left' => 'ci.collection_id', 'right' => 'c.id'],
],
'measure' => [
    'agg' => 'count_distinct', 'column' => 'base.id',
    'target' => ['type' => 'user', 'id_column' => 'c.owner_id'], // owner on the join
],
// filter by a category via a qualified static filter field:
//   StatisticFilter { field: 'cic.category_id', operator: '=', value: '3' }
```

### Unordered self-cross pairs (`pair_group`)

Two axes referencing the **same entity type** can be canonicalized into an
unordered pair by sharing a `pair_group` token. `(A,B)` and `(B,A)` then collapse
to one cell (stored as `min ≤ max`). This is the HSC `voting_pair_stats` shape:

```php
'dimensions' => [
    ['name' => 'voting_type', 'kind' => 'scalar', 'column' => 'voting_type'],
    ['name' => 'platform_a', 'kind' => 'entity', 'id_column' => 'platform_a_id',
        'type' => 'platform', 'pair_group' => 'platforms'],
    ['name' => 'platform_b', 'kind' => 'entity', 'id_column' => 'platform_b_id',
        'type' => 'platform', 'pair_group' => 'platforms'],
],
```

## Cell storage

- `target_statistics` gains `dimension_key` (the canonical hash of the extra
  axes) plus a DB `UNIQUE (statistic_id, target_type, target_id, dimension_key)`
  — cells are `updateOrCreate`d on this tuple (the dup-key-500 fix).
- `value` holds the scalar measure (fast reads + `SUM` rollups across one axis);
  `result` holds the full `{measure, tuple}`.
- `target_statistic_dimensions` holds one row per extra axis value
  (`axis`, `value_type`/`value_id` for entities, `value_scalar` otherwise),
  indexed by `(statistic_id, axis, value_id)` and `(statistic_id, axis,
  value_scalar)` so partial-tuple slice reads don't need a wide join.

### Soft-delete guard

The engine writes `base.deleted_at IS NULL` EXPLICITLY into the `GROUP BY` query
(when the base table has the column). It does NOT rely on the SoftDeletes global
scope — lowering a relation to the base builder drops that scope, which is the
known count-inflation bug this fixes.

## Incremental maintenance

`AggregatedModelObserver` dispatches `MaintainStatisticCellsJob` on
create/update/delete/restore of a base-table row. For each engine-backed
statistic over that table it recomputes only the affected primary target's cells
(a bounded `GROUP BY` scoped to that owner); an update that moves owners settles
both the old and new scopes.

When the primary target lives on a **joined** table (a qualified `id_column`
such as `c.owner_id`), the changed base-table row does not carry that column, so
a bounded per-target recompute cannot be scoped precisely from the row alone. In
that case the maintenance path falls back to a FULL `recompute($statistic)` and
logs the fallback rather than silently mis-scoping. A precise cross-join bounded
recompute is a possible future refinement.

## Derived statistics (materialized)

A `kind => 'derived'` statistic is an expression over other statistics' cells,
**materialized** and recomputed eagerly (push) whenever an operand it depends on
is recomputed. Reads return a stored value; correctness comes from the full
recompute, never from lazy read-time evaluation.

```php
Statistic::create([
    'name' => 'best_ratio',
    'model' => 'platform_comparison',
    'relation' => '',
    'kind' => 'derived',
    'derived_definition' => [
        'operation' => 'ratio',            // ratio | difference | sum
        'operands'  => [$numeratorId, $denominatorId], // base statistic ids
    ],
]);

$svc = app(DerivedStatisticService::class);
$svc->syncDependencies($derived); // persists statistic_dependencies edges
$svc->recompute($derived);        // materializes cells from operand cells
```

- Operands are aligned by matching dimension-tuple; a derived cell carries the
  same `dimension_key` as its operands (so a per-cell "coverage %" exists).
- Zero / missing denominator stores `null` (never divides by zero, never throws).
- The recompute trigger runs at the END of the base recompute path
  (`RecountStatisticJob` dispatches `RecomputeDerivedStatisticJob` once operands
  are settled), so materialized values stay correct on every write.

### v1 limitations (by design)

- **Derived-on-derived is FORBIDDEN.** An operand must be a base (non-derived)
  statistic; this is validated at recompute time and keeps the recompute a single
  terminating pass. Chaining / topological ordering is out of scope for v1.
- **A materialized ratio/avg cell is non-additive.** NEVER sum or roll up a
  stored derived ratio across a dimension — a rolled-up ratio must be re-derived
  from rolled-up operands. Materializing the point value is fine; aggregating the
  materialized ratio is not.
- Combinatorial "missing pairs" and threshold-parameterized counts stay in
  consumer code by design.

The derived layer is separable: remove `DerivedStatisticService`,
`RecomputeDerivedStatisticJob` and the `statistic_dependencies` table and the
core engine is untouched.
