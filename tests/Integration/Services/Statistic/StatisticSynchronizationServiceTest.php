<?php

declare(strict_types=1);

namespace Polis\Tests\Integration\Services\Statistic;

use App\Models\Collection\Collection;
use App\Models\Statistic\Statistic;
use App\Models\Statistic\TargetStatistic;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\DB;
use Polis\Services\Statistic\StatisticSynchronizationService;
use Polis\Tests\Application\ApplicationTestCase;

class StatisticSynchronizationServiceTest extends ApplicationTestCase
{
    private StatisticSynchronizationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(StatisticSynchronizationService::class);
    }

    public function test_create_target_statistics_for_statistic(): void
    {
        // Clean up any existing collections and statistics
        DB::table('collections')->delete();
        DB::table('statistics')->delete();

        // Create two collections
        $collections = Collection::factory()->count(2)->create();

        // Create a statistic for collections
        $statistic = Statistic::factory()->create([
            'model' => 'collection',
        ]);

        // Create target statistics for the statistic
        $targetStatistics = $this->service->createTargetStatisticsForStatistic($statistic);

        // Assert we got a collection of target statistics
        $this->assertInstanceOf(EloquentCollection::class, $targetStatistics);
        $this->assertCount(2, $targetStatistics);

        // Assert each target statistic was created correctly
        foreach ($targetStatistics as $targetStatistic) {
            $this->assertInstanceOf(TargetStatistic::class, $targetStatistic);
            $this->assertEquals($statistic->id, $targetStatistic->statistic_id);
            $this->assertEquals('collection', $targetStatistic->target_type);
            $this->assertTrue($collections->pluck('id')->contains($targetStatistic->target_id));
        }
    }

    public function test_synchronize_target_statistics_with_no_existing_targets(): void
    {
        // Clean up any existing statistics
        DB::table('statistics')->delete();

        // Create a collection
        $collection = Collection::factory()->create();

        // Create a statistic for collections
        $statistic = Statistic::factory()->create([
            'model' => 'collection',
        ]);

        // Synchronize target statistics
        $result = $this->service->synchronizeTargetStatistics($collection);

        // Assert we got a collection of target statistics
        $this->assertInstanceOf(EloquentCollection::class, $result);
        $this->assertCount(1, $result);

        // Assert the target statistic was created correctly
        $targetStatistic = $result->first();
        $this->assertEquals($statistic->id, $targetStatistic->statistic_id);
        $this->assertEquals($collection->id, $targetStatistic->target_id);
        $this->assertEquals('collection', $targetStatistic->target_type);
    }

    public function test_synchronize_target_statistics_with_existing_targets(): void
    {
        // Clean up any existing statistics
        DB::table('statistics')->delete();

        // Create a collection
        $collection = Collection::factory()->create();

        // Create two statistics for collections
        $existingStatistic = Statistic::factory()->create([
            'model' => 'collection',
        ]);
        $newStatistic = Statistic::factory()->create([
            'model' => 'collection',
        ]);

        // Create an existing target statistic
        TargetStatistic::factory()->create([
            'statistic_id' => $existingStatistic->id,
            'target_id' => $collection->id,
            'target_type' => 'collection',
        ]);

        // Synchronize target statistics
        $result = $this->service->synchronizeTargetStatistics($collection);

        // Assert we got a collection with both target statistics
        $this->assertInstanceOf(EloquentCollection::class, $result);
        $this->assertCount(2, $result);

        // Assert both target statistics exist
        $this->assertTrue($result->contains('statistic_id', $existingStatistic->id));
        $this->assertTrue($result->contains('statistic_id', $newStatistic->id));
    }
}
