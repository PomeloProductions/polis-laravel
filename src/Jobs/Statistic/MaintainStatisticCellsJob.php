<?php

declare(strict_types=1);

namespace Polis\Jobs\Statistic;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Polis\Observers\AggregatedModelObserver;
use Polis\Services\Statistic\TupleDeltaMaintenanceService;

/**
 * Incremental tuple-delta maintenance for a changed base-table row.
 *
 * Dispatched by {@see AggregatedModelObserver} on
 * create/update/delete/restore of an aggregatable row. Recomputes only the
 * CELLS of the affected primary target(s) for every engine-backed statistic,
 * rather than recomputing a whole statistic blindly.
 *
 * The changed row is carried as a (class, attribute-snapshot) pair rather than an
 * Eloquent identity: on a delete the row may no longer be fetchable, and the
 * maintenance only needs the row's primary-target id COLUMNS (its owner), which
 * the snapshot preserves. A reconstructed, non-persisted instance is handed to
 * the service — it is read for its column values, never saved.
 */
class MaintainStatisticCellsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /** @var class-string<Model> */
    private readonly string $modelClass;

    /** @var array<string, mixed> */
    private readonly array $attributes;

    /**
     * @param  array<string, int|string|null>  $previousTargetIds  Per-statistic
     *                                                             pre-update primary-target id (for a row that moved owners).
     */
    public function __construct(
        Model $changed,
        private readonly array $previousTargetIds = [],
    ) {
        $this->modelClass = $changed::class;
        $this->attributes = $changed->getAttributes();
    }

    public function handle(TupleDeltaMaintenanceService $maintenanceService): void
    {
        /** @var Model $instance */
        $instance = new $this->modelClass;
        $instance->setRawAttributes($this->attributes, true);

        $maintenanceService->maintainForChangedRow($instance, $this->previousTargetIds);
    }
}
