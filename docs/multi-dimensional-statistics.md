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
