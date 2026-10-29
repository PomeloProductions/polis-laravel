<?php

declare(strict_types=1);

namespace Polis\Jobs\Statistic;

use App\Models\Statistic\Statistic;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Polis\Contracts\Services\Statistic\TargetStatisticProcessingServiceContract;
use Polis\Services\Statistic\DerivedStatisticService;
use Polis\Services\Statistic\MultiDimensionalStatisticService;
use Polis\Support\Statistic\StatisticDefinition;

class RecountStatisticJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private readonly Statistic $statistic
    ) {}

    /**
     * Execute the job.
     *
     * Routes by statistic kind:
     *   - DERIVED            -> recompute its cells from current operand cells.
     *   - engine-backed      -> one GROUP BY full recompute of every cell, then
     *                           trigger dependent derived recomputes.
     *   - legacy single-dim  -> the unchanged per-target in-PHP path, then
     *                           trigger dependent derived recomputes.
     */
    public function handle(
        TargetStatisticProcessingServiceContract $processingService,
        MultiDimensionalStatisticService $multiDimensionalService,
        DerivedStatisticService $derivedStatisticService,
        Dispatcher $dispatcher,
    ): void {
        $definition = StatisticDefinition::for($this->statistic);

        if ($definition->isDerived()) {
            $derivedStatisticService->syncDependencies($this->statistic);
            $derivedStatisticService->recompute($this->statistic);

            return;
        }

        if ($definition->usesAggregationEngine()) {
            $multiDimensionalService->recompute($this->statistic);
        } else {
            foreach ($this->statistic->targetStatistics as $targetStatistic) {
                $processingService->processSingleTargetStatistic($targetStatistic);
            }
        }

        // Base recompute done + operands settled — push the materialized derived
        // statistics that depend on this one.
        $dispatcher->dispatch(new RecomputeDerivedStatisticJob($this->statistic->id));
    }
}
