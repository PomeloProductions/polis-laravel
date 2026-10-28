<?php

declare(strict_types=1);

namespace Polis\Services\Statistic;

use App\Models\Statistic\Statistic;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;
use Polis\Support\Statistic\DateBucketResolver;
use Polis\Support\Statistic\DimensionDefinition;
use Polis\Support\Statistic\DimensionTuple;
use Polis\Support\Statistic\DimensionValue;
use Polis\Support\Statistic\JoinDefinition;
use Polis\Support\Statistic\StatisticDefinition;
use RuntimeException;

/**
 * Class MultiDimensionalAggregationService
 *
 * The GROUP BY aggregation engine for multi-dimensional statistics. It replaces
 * the in-PHP relation traversal (which lowered relations to the base builder and
 * therefore BYPASSED the SoftDeletes global scope — a real count-inflation bug)
 * with ONE SQL query:
 *
 *   SELECT <primary target cols>, <axis cols>, <measure aggregate>
 *   FROM <base table of statistic.model>
 *   [JOIN <declared related tables>]
 *   WHERE <static filters> AND <deleted_at IS NULL baked in>
 *   GROUP BY <primary target cols>, <axis cols>
 *
 * Declarative `joins` let a statistic COUNT one base table while its axes /
 * filters / measure / primary target reference RELATED tables (e.g. a year axis
 * on a joined release_dates table, or an owner on a joined collections table),
 * so no bespoke per-shape SQL VIEW is needed. EVERY column reference is run
 * through {@see self::qualify()}, which defaults a bare name to the base alias
 * and leaves an already-qualified `alias.column` untouched; with no joins
 * declared every reference is bare and the engine behaves exactly as before.
 *
 * The soft-delete guard is written EXPLICITLY, not inherited from the global
 * scope, precisely because the scope does not survive the base-builder lowering.
 *
 * It produces ALL cells in one pass as plain rows; it does NOT enumerate the
 * cross-product (sparse: only groups that actually have rows become cells) and
 * never does an unbounded `::query()->get()->all()`.
 *
 * date_bucket axes are grouped in SQL via a driver-aware bucket expression so
 * count_distinct stays correct and grouping never spills into PHP; the bucket is
 * resolved in the configured timezone with a configurable week-start.
 */
class MultiDimensionalAggregationService
{
    private const BASE_ALIAS = 'base';

    public function __construct(
        private readonly DateBucketResolver $dateBucketResolver,
    ) {}

    /**
     * Compute cells for a multi-dimensional statistic.
     *
     * @param  int|string|null  $scopeTargetId  When non-null (and the statistic
     *                                          has a primary-target binding), restrict the GROUP BY to this one
     *                                          primary target — a BOUNDED recompute for incremental maintenance.
     * @return array<int, AggregatedCell> One per non-empty GROUP BY group.
     */
    public function aggregate(Statistic $statistic, int|string|null $scopeTargetId = null): array
    {
        $definition = StatisticDefinition::for($statistic);

        if (! $definition->usesAggregationEngine()) {
            throw new RuntimeException(
                "Statistic [{$statistic->id}] does not declare dimensions + measure; "
                .'the GROUP BY engine only serves multi-dimensional statistics.'
            );
        }

        $baseTable = $this->resolveBaseTable($statistic);
        $measure = $definition->measure();
        $dimensions = $definition->dimensions();
        $primaryTarget = $definition->primaryTarget();

        $query = DB::table($baseTable.' as '.self::BASE_ALIAS);

        // --- JOINS: apply declarative joins in declaration order ------------
        $this->applyJoins($query, $definition->joins());

        if ($scopeTargetId !== null && $primaryTarget !== null) {
            $query->where($this->qualify($primaryTarget['id_column']), $scopeTargetId);
        }

        // --- SELECT: measure + grouping columns -----------------------------
        $selects = [DB::raw($measure->toSqlExpression(self::BASE_ALIAS).' as measure_value')];
        $groupBy = [];

        if ($primaryTarget !== null) {
            $primaryCol = $this->qualify($primaryTarget['id_column']);
            $selects[] = DB::raw($primaryCol.' as primary_target_id');
            $groupBy[] = $primaryCol;
        }

        $pairExpressions = $this->pairAxisExpressions($dimensions);

        foreach ($dimensions as $dimension) {
            foreach ($this->axisSelectColumns($dimension, $pairExpressions) as $alias => $expression) {
                $selects[] = DB::raw($expression.' as '.$alias);
                $groupBy[] = DB::raw($expression);
            }
        }

        $query->select($selects);

        // --- WHERE: static filters + explicit soft-delete guard -------------
        $this->applyStaticFilters($query, $statistic);
        $this->applySoftDeleteGuard($query, $baseTable);

        foreach ($groupBy as $group) {
            $query->groupBy($group);
        }

        // --- Execute + fold into cells --------------------------------------
        $rows = $query->get();
        $cells = [];

        foreach ($rows as $row) {
            $cells[] = $this->rowToCell($row, $dimensions, $primaryTarget);
        }

        return $cells;
    }

    /**
     * The grouping/select column expressions for one axis, keyed by column
     * alias. Entity axes expose id (and, when resolved from a column, type);
     * scalar axes expose the raw column; date_bucket axes expose a driver-aware
     * bucket expression so grouping stays in SQL.
     *
     * Paired axes (an unordered self-cross pair) are canonicalized IN SQL via a
     * LEAST/GREATEST (MySQL) / MIN/MAX (SQLite) pair expression, so (A,B) and
     * (B,A) land in the SAME GROUP BY group and their counts sum correctly
     * instead of forming two half-cells.
     *
     * @param  array<string, string>  $pairExpressions  axis-name => SQL expression
     *                                                  for pre-canonicalized paired axes.
     * @return array<string, string> alias => sql expression
     */
    private function axisSelectColumns(DimensionDefinition $dimension, array $pairExpressions): array
    {
        $axis = $dimension->name();

        if ($dimension->kind() === DimensionDefinition::KIND_DATE_BUCKET) {
            return [
                'axis_'.$axis => $this->dateBucketSqlExpression($dimension),
            ];
        }

        $columns = [];

        if (isset($pairExpressions[$axis])) {
            $columns['axis_'.$axis] = $pairExpressions[$axis];
        } elseif ($dimension->column() !== null) {
            $columns['axis_'.$axis] = $this->qualify($dimension->column());
        }

        // An entity axis whose morph type is resolved per-row from a column.
        if ($dimension->kind() === DimensionDefinition::KIND_ENTITY && $dimension->entityTypeColumn() !== null) {
            $columns['axistype_'.$axis] = $this->qualify($dimension->entityTypeColumn());
        }

        return $columns;
    }

    /**
     * Build the per-axis SQL expressions that canonicalize unordered pairs. For a
     * pair_group of exactly two axes the first (by declaration order) gets the
     * MIN of the two id columns and the second gets the MAX, so the pair is
     * stored as (min ≤ max) and (A,B)/(B,A) collapse to one SQL group.
     *
     * @param  array<int, DimensionDefinition>  $dimensions
     * @return array<string, string> axis-name => canonical SQL expression
     */
    private function pairAxisExpressions(array $dimensions): array
    {
        $byGroup = [];

        foreach ($dimensions as $dimension) {
            if ($dimension->isPaired() && $dimension->column() !== null) {
                $byGroup[(string) $dimension->pairGroup()][] = $dimension;
            }
        }

        $driver = DB::connection()->getDriverName();
        $least = $driver === 'sqlite' ? 'MIN' : 'LEAST';
        $greatest = $driver === 'sqlite' ? 'MAX' : 'GREATEST';

        $expressions = [];

        foreach ($byGroup as $members) {
            // Only canonicalize true 2-axis pairs; other cardinalities fall back
            // to raw per-axis grouping (and the PHP canonicalizer).
            if (count($members) !== 2) {
                continue;
            }

            [$first, $second] = $members;
            $colA = $this->qualify($first->column());
            $colB = $this->qualify($second->column());

            $expressions[$first->name()] = "{$least}({$colA}, {$colB})";
            $expressions[$second->name()] = "{$greatest}({$colA}, {$colB})";
        }

        return $expressions;
    }

    /**
     * A database-driver-aware SQL expression that normalizes the axis's source
     * timestamp into its bucket label, shifted into the configured timezone.
     *
     * day/week/month/year map to strftime (SQLite) / DATE_FORMAT (MySQL) over
     * the tz-shifted instant. Week buckets honor a configurable week-start day.
     */
    private function dateBucketSqlExpression(DimensionDefinition $dimension): string
    {
        $driver = DB::connection()->getDriverName();
        $column = $this->qualify($dimension->column());
        $granularity = $dimension->granularity();
        $timezone = $dimension->timezone();
        $weekStart = $dimension->weekStart();

        $offsetSeconds = $this->timezoneOffsetSeconds($timezone);

        if ($driver === 'sqlite') {
            // Shift the stored UTC instant into local time, then format.
            $local = "datetime({$column}, '{$offsetSeconds} seconds')";

            return match ($granularity) {
                DateBucketResolver::YEAR => "strftime('%Y', {$local})",
                DateBucketResolver::MONTH => "strftime('%Y-%m', {$local})",
                DateBucketResolver::WEEK => $this->sqliteWeekExpression($local, $weekStart),
                default => "strftime('%Y-%m-%d', {$local})",
            };
        }

        // MySQL / MariaDB.
        $local = "CONVERT_TZ({$column}, '+00:00', '".$this->sqlOffset($offsetSeconds)."')";

        return match ($granularity) {
            DateBucketResolver::YEAR => "DATE_FORMAT({$local}, '%Y')",
            DateBucketResolver::MONTH => "DATE_FORMAT({$local}, '%Y-%m')",
            DateBucketResolver::WEEK => $this->mysqlWeekExpression($local, $weekStart),
            default => "DATE_FORMAT({$local}, '%Y-%m-%d')",
        };
    }

    /**
     * SQLite weekly bucket: walk the local date back to the configured week-start
     * day, then label by the ISO week number AND the boundary DATE (so any
     * week-start is unambiguous and chronologically sortable — matching
     * {@see DateBucketResolver}'s 'W%W-Y-m-d' label).
     * strftime('%w') returns 0=Sunday..6=Saturday, matching our week-start enum.
     */
    private function sqliteWeekExpression(string $local, int $weekStart): string
    {
        $weekStart = ($weekStart % 7 + 7) % 7;
        // days to subtract = (dow - weekStart + 7) % 7
        $dow = "CAST(strftime('%w', {$local}) AS INTEGER)";
        $delta = "(({$dow} - {$weekStart} + 7) % 7)";
        $boundary = "date({$local}, '-' || {$delta} || ' days')";

        return "'W-' || strftime('%Y-%m-%d', {$boundary})";
    }

    /**
     * MySQL weekly bucket: subtract to the configured week-start boundary and
     * label by ISO week number AND the boundary DATE (matching the PHP resolver).
     * MySQL WEEKDAY() is 0=Monday..6=Sunday, so convert to a Sunday-based dow.
     */
    private function mysqlWeekExpression(string $local, int $weekStart): string
    {
        $weekStart = ($weekStart % 7 + 7) % 7;
        // Sunday-based dow: (WEEKDAY + 1) % 7
        $dow = "((WEEKDAY({$local}) + 1) % 7)";
        $delta = "(({$dow} - {$weekStart} + 7) % 7)";
        $boundary = "DATE_SUB({$local}, INTERVAL {$delta} DAY)";

        return "CONCAT('W-', DATE_FORMAT({$boundary}, '%Y-%m-%d'))";
    }

    /**
     * Build one cell from a grouped result row: measure value + resolved axis
     * values + the canonical dimension_key + the primary-target binding.
     *
     * @param  array<int, DimensionDefinition>  $dimensions
     * @param  array{type: string, id_column: string}|null  $primaryTarget
     */
    private function rowToCell(object $row, array $dimensions, ?array $primaryTarget): AggregatedCell
    {
        $axisValues = [];
        $pairGroups = $this->resolvePairGroups($dimensions);

        foreach ($dimensions as $dimension) {
            $axisValues[$dimension->name()] = $this->rowAxisValue($row, $dimension);
        }

        $tuple = (new DimensionTuple($axisValues, $pairGroups))->canonicalized();

        return new AggregatedCell(
            measureValue: (float) ($row->measure_value ?? 0),
            targetType: $primaryTarget['type'] ?? null,
            targetId: $primaryTarget !== null ? ($row->primary_target_id ?? null) : null,
            dimensionTuple: $tuple,
        );
    }

    /**
     * Resolve a single axis's DimensionValue from a grouped row.
     */
    private function rowAxisValue(object $row, DimensionDefinition $dimension): DimensionValue
    {
        $axis = $dimension->name();
        $raw = $row->{'axis_'.$axis} ?? null;

        return match ($dimension->kind()) {
            DimensionDefinition::KIND_ENTITY => DimensionValue::entity(
                $dimension->entityType() ?? ($row->{'axistype_'.$axis} ?? ''),
                $raw ?? '',
            ),
            DimensionDefinition::KIND_DATE_BUCKET => DimensionValue::dateBucket((string) $raw),
            default => DimensionValue::scalar($raw),
        };
    }

    /**
     * Collect the axis-name groupings that form unordered pairs (same
     * pair_group token), so the tuple canonicalizer can collapse (A,B)/(B,A).
     *
     * @param  array<int, DimensionDefinition>  $dimensions
     * @return array<int, array<int, string>>
     */
    private function resolvePairGroups(array $dimensions): array
    {
        $byGroup = [];

        foreach ($dimensions as $dimension) {
            if ($dimension->isPaired()) {
                $byGroup[(string) $dimension->pairGroup()][] = $dimension->name();
            }
        }

        return array_values(array_filter(
            $byGroup,
            static fn (array $members): bool => count($members) > 1,
        ));
    }

    /**
     * Apply the statistic's static (non-`unique`) filters as SQL WHERE clauses
     * via the shared translator so the multi-dim engine and the legacy path
     * agree on operator semantics.
     */
    private function applyStaticFilters(QueryBuilder $query, Statistic $statistic): void
    {
        $filters = $statistic->filters
            ->reject(static fn ($filter): bool => $filter->operator === 'unique');

        (new StatisticFilterToQuery)->applyFilters($query, $filters);
    }

    /**
     * BAKE IN the soft-delete guard. The base-builder lowering drops the
     * SoftDeletes global scope, so we add `deleted_at IS NULL` explicitly
     * whenever the base table has the column — this is the fix for the
     * count-inflation bug.
     */
    private function applySoftDeleteGuard(QueryBuilder $query, string $baseTable): void
    {
        if ($this->hasDeletedAt($baseTable)) {
            $query->whereNull(self::BASE_ALIAS.'.deleted_at');
        }
    }

    /**
     * Apply the statistic's declarative joins in declaration order so a later
     * join may reference an earlier join's alias. Each join carries its own
     * soft-delete guard:
     *   - INNER → `alias.deleted_at IS NULL` added to the WHERE clause.
     *   - LEFT  → added to the ON closure, so a LEFT join is NOT silently
     *     narrowed into an inner one by excluding the all-NULL unmatched rows.
     * The guard auto-skips when the joined table has no `deleted_at` column.
     *
     * @param  array<int, JoinDefinition>  $joins
     */
    private function applyJoins(QueryBuilder $query, array $joins): void
    {
        foreach ($joins as $join) {
            $guard = $join->guardsSoftDeletes() && $this->hasDeletedAt($join->table());
            $method = $join->isLeft() ? 'leftJoin' : 'join';

            $query->{$method}(
                $join->tableWithAlias(),
                function ($clause) use ($join, $guard): void {
                    $clause->on($join->left(), '=', $join->right());

                    foreach ($join->onExtra() as [$col, $op, $val]) {
                        $clause->where($col, $op, $val);
                    }

                    // LEFT join: guard IN the ON clause so unmatched rows survive.
                    if ($join->isLeft() && $guard) {
                        $clause->whereNull($join->alias().'.deleted_at');
                    }
                },
            );

            // INNER join: guard in the WHERE clause (equivalent to the ON guard
            // for an inner join, and keeps the join condition readable).
            if (! $join->isLeft() && $guard) {
                $query->whereNull($join->alias().'.deleted_at');
            }
        }
    }

    /**
     * Qualify a bare column reference with the base alias; an already-qualified
     * reference (containing a dot, e.g. `rd.release_date` or `c.owner_id`) is
     * returned untouched so axes/filters/measure/target can point at a joined
     * table. With no joins declared every reference is bare, so this reproduces
     * the previous `base.<column>` behavior byte-for-byte.
     */
    private function qualify(string $reference): string
    {
        if (str_contains($reference, '.')) {
            return $reference;
        }

        return self::BASE_ALIAS.'.'.$reference;
    }

    /**
     * Whether a physical table has a `deleted_at` column (so the soft-delete
     * guard is only applied where it is meaningful).
     */
    private function hasDeletedAt(string $table): bool
    {
        return DB::connection()->getSchemaBuilder()->hasColumn($table, 'deleted_at');
    }

    /**
     * Resolve the physical base table for the statistic's `model` morph alias.
     */
    private function resolveBaseTable(Statistic $statistic): string
    {
        $modelClass = Relation::getMorphedModel($statistic->model);

        if (! $modelClass) {
            throw new RuntimeException("No morph map found for model type: {$statistic->model}");
        }

        /** @var Model $instance */
        $instance = new $modelClass;

        return $instance->getTable();
    }

    /**
     * The timezone's current UTC offset in seconds (used to shift stored UTC
     * instants into local time inside the SQL bucket expression).
     */
    private function timezoneOffsetSeconds(string $timezone): int
    {
        return (new \DateTimeZone($timezone))->getOffset(new \DateTime('now', new \DateTimeZone('UTC')));
    }

    /**
     * Format a signed second offset as an SQL '+HH:MM' / '-HH:MM' string for
     * MySQL CONVERT_TZ.
     */
    private function sqlOffset(int $seconds): string
    {
        $sign = $seconds < 0 ? '-' : '+';
        $seconds = abs($seconds);
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);

        return sprintf('%s%02d:%02d', $sign, $hours, $minutes);
    }
}
