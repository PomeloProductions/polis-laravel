<?php

declare(strict_types=1);

namespace Polis\Jobs\Statistic;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Polis\Services\Statistic\DerivedStatisticService;

/**
 * Recompute every DERIVED statistic that depends on a base statistic.
 *
 * Dispatched at the END of a base statistic's recompute path (once its operand
 * cells are settled), so derived cells are MATERIALIZED eagerly from the fresh
 * operand values rather than evaluated lazily at read. A dedicated job keeps the
 * derived recompute ordered after the operands and lets it run on the queue.
 */
class RecomputeDerivedStatisticJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        private readonly int $baseStatisticId,
    ) {}

    public function handle(DerivedStatisticService $derivedStatisticService): void
    {
        $derivedStatisticService->recomputeDependentsOf($this->baseStatisticId);
    }
}
