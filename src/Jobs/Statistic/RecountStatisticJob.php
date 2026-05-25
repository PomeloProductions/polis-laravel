<?php

declare(strict_types=1);

namespace Polis\Jobs\Statistic;

use App\Models\Statistic\Statistic;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Polis\Contracts\Services\Statistic\TargetStatisticProcessingServiceContract;

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
     */
    public function handle(TargetStatisticProcessingServiceContract $processingService): void
    {
        foreach ($this->statistic->targetStatistics as $targetStatistic) {
            $processingService->processSingleTargetStatistic($targetStatistic);
        }
    }
}
