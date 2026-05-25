<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Services\Statistic;

use App\Models\Collection\Collection as CollectionModel;
use App\Models\Statistic\Statistic;
use App\Models\Statistic\StatisticFilter;
use App\Models\Statistic\TargetStatistic;
use Illuminate\Database\Eloquent\Collection as BaseCollection;
use Illuminate\Database\Eloquent\Model;
use Mockery;
use Mockery\MockInterface;
use Polis\Contracts\Repositories\Statistic\TargetStatisticRepositoryContract;
use Polis\Contracts\Services\Relations\RelationTraversalServiceContract;
use Polis\Services\Statistic\TargetStatisticProcessingService;
use Polis\Tests\TestCase;

/**
 * Class TargetStatisticProcessingServiceTest
 */
class TargetStatisticProcessingServiceTest extends TestCase
{
    /**
     * @var RelationTraversalServiceContract|MockInterface
     */
    private MockInterface $relationTraversalService;

    /**
     * @var TargetStatisticRepositoryContract|MockInterface
     */
    private MockInterface $targetStatisticRepository;

    private TargetStatisticProcessingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->relationTraversalService = Mockery::mock(RelationTraversalServiceContract::class);
        $this->targetStatisticRepository = Mockery::mock(TargetStatisticRepositoryContract::class);
        $this->service = new TargetStatisticProcessingService(
            $this->relationTraversalService,
            $this->targetStatisticRepository
        );
    }

    public function test_process_single_target_statistic_with_total_count()
    {
        $relatedModels = new BaseCollection([
            $this->createModelWithValue('test1', 10),
            $this->createModelWithValue('test2', 20),
        ]);

        $filter = new StatisticFilter([
            'operator' => '>',
            'field' => 'value',
            'value' => '15',
        ]);

        $statistic = new Statistic([
            'relation' => 'test_relation',
        ]);
        $statistic->filters = new BaseCollection([$filter]);

        $targetStatistic = new TargetStatistic([
            'id' => 1,
            'target_id' => 1,
            'target_type' => 'collection',
            'statistic_id' => 1,
        ]);
        $targetStatistic->setRelation('statistic', $statistic);
        $targetStatistic->setRelation('target', new CollectionModel);

        $this->relationTraversalService->shouldReceive('traverseRelations')
            ->with($targetStatistic->target, 'test_relation')
            ->andReturn($relatedModels);

        $this->targetStatisticRepository->shouldReceive('update')
            ->with(Mockery::on(function ($targetStat) {
                return $targetStat instanceof TargetStatistic;
            }), Mockery::on(function ($data) {
                return isset($data['result']) && $data['result']['total'] === 1;
            }))
            ->once();

        $this->service->processSingleTargetStatistic($targetStatistic);
    }

    public function test_process_single_target_statistic_with_unique_values()
    {
        $relatedModels = new BaseCollection([
            $this->createModelWithValue('collection1', 10),
            $this->createModelWithValue('collection1', 20),
            $this->createModelWithValue('collection2', 30),
            $this->createModelWithValue('collection2', 40),
        ]);

        $uniqueFilter = new StatisticFilter([
            'operator' => 'unique',
            'field' => 'name',
        ]);

        $valueFilter = new StatisticFilter([
            'operator' => '>',
            'field' => 'value',
            'value' => '15',
        ]);

        $statistic = new Statistic([
            'relation' => 'test_relation',
        ]);
        $statistic->filters = new BaseCollection([$uniqueFilter, $valueFilter]);

        $targetStatistic = new TargetStatistic([
            'id' => 1,
            'target_id' => 1,
            'target_type' => 'collection',
            'statistic_id' => 1,
        ]);
        $targetStatistic->setRelation('statistic', $statistic);
        $targetStatistic->setRelation('target', new CollectionModel);

        $this->relationTraversalService->shouldReceive('traverseRelations')
            ->with($targetStatistic->target, 'test_relation')
            ->andReturn($relatedModels);

        $expectedResult = [
            'collection1' => 1,
            'collection2' => 2,
        ];

        $this->targetStatisticRepository->shouldReceive('update')
            ->with(Mockery::type(TargetStatistic::class), Mockery::subset(['result' => $expectedResult]))
            ->once();

        $this->service->processSingleTargetStatistic($targetStatistic);
    }

    private function createModelWithValue(string $name, int $value): Model
    {
        $model = new CollectionModel;
        $model->name = $name;
        $model->value = $value;

        return $model;
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
