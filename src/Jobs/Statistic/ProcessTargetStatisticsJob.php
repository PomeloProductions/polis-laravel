<?php

declare(strict_types=1);

namespace Polis\Jobs\Statistic;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Polis\Contracts\Models\CanBeStatisticTargetContract;
use Polis\Contracts\Services\Statistic\TargetStatisticProcessingServiceContract;

class ProcessTargetStatisticsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private CanBeStatisticTargetContract $target;

    /**
     * Create a new job instance.
     */
    public function __construct(CanBeStatisticTargetContract $target)
    {
        $this->target = $target;
    }

    /**
     * Execute the job.
     */
    public function handle(TargetStatisticProcessingServiceContract $processingService): void
    {
        foreach ($this->target->targetStatistics as $targetStatistic) {
            $processingService->processSingleTargetStatistic($targetStatistic);
        }
    }
}
