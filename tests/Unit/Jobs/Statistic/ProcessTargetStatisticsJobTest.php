<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Jobs\Statistic;

use App\Models\Collection\Collection;
use App\Models\Statistic\Statistic;
use App\Models\Statistic\TargetStatistic;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Mockery;
use Mockery\MockInterface;
use Polis\Contracts\Services\Statistic\TargetStatisticProcessingServiceContract;
use Polis\Jobs\Statistic\ProcessTargetStatisticsJob;
use Polis\Tests\TestCase;

/**
 * Class ProcessTargetStatisticsJobTest
 */
class ProcessTargetStatisticsJobTest extends TestCase
{
    public function test_handle_processes_all_target_statistics(): void
    {
        // Create a real Collection model without saving
        $collection = new Collection;
        $collection->id = 1;
        $collection->name = 'Test Collection';

        // Create a real Statistic model without saving
        $statistic = new Statistic;
        $statistic->id = 1;
        $statistic->name = 'Test Statistic';

        // Create real TargetStatistic models without saving
        $targetStatistics = new EloquentCollection([
            new TargetStatistic([
                'id' => 1,
                'target_id' => $collection->id,
                'target_type' => 'collection',
                'statistic_id' => $statistic->id,
            ]),
            new TargetStatistic([
                'id' => 2,
                'target_id' => $collection->id,
                'target_type' => 'collection',
                'statistic_id' => $statistic->id,
            ]),
        ]);

        // Associate the target statistics with the collection
        $collection->setRelation('targetStatistics', $targetStatistics);

        /** @var TargetStatisticProcessingServiceContract|MockInterface $processingService */
        $processingService = Mockery::mock(TargetStatisticProcessingServiceContract::class);

        // Setup expectations for each statistic
        foreach ($targetStatistics as $targetStatistic) {
            $processingService->shouldReceive('processSingleTargetStatistic')
                ->with($targetStatistic)
                ->once();
        }

        $job = new ProcessTargetStatisticsJob($collection);
        $job->handle($processingService);
    }

    public function test_handle_with_no_target_statistics(): void
    {
        // Create a real Collection model without saving
        $collection = new Collection;
        $collection->id = 1;
        $collection->name = 'Test Collection';

        // Set empty relation
        $collection->setRelation('targetStatistics', new EloquentCollection([]));

        /** @var TargetStatisticProcessingServiceContract|MockInterface $processingService */
        $processingService = Mockery::mock(TargetStatisticProcessingServiceContract::class);
        $processingService->shouldNotReceive('processSingleTargetStatistic');

        $job = new ProcessTargetStatisticsJob($collection);
        $job->handle($processingService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
