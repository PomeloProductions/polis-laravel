<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Services\Statistic;

use App\Models\Collection\Collection as CollectionModel;
use App\Models\Statistic\Statistic;
use App\Models\Statistic\StatisticFilter;
use App\Models\Statistic\TargetStatistic;
use Illuminate\Database\Eloquent\Collection as BaseCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Mockery;
use Mockery\MockInterface;
use Polis\Contracts\Models\CanAggregateViaQueryContract;
use Polis\Contracts\Models\CanBeStatisticTargetContract;
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

    public function test_ensure_and_recompute_single_finds_existing_row_computes_via_same_path_and_writes_result()
    {
        $statistic = new Statistic(['relation' => 'releases']);
        $statistic->id = 7;

        $target = new FakeAggregatingTarget(['total' => 3]);

        $existing = new TargetStatistic([
            'target_id' => 1,
            'target_type' => 'year',
            'statistic_id' => 7,
        ]);

        // An existing row is found — not re-created.
        $this->targetStatisticRepository->shouldReceive('findForTarget')
            ->once()
            ->with($target, 7)
            ->andReturn($existing);
        $this->targetStatisticRepository->shouldReceive('createForTarget')->never();

        // The result is computed through the SAME path the queued recount uses
        // (the target's aggregateForStatistic push-down) and WRITTEN back.
        $this->targetStatisticRepository->shouldReceive('update')
            ->once()
            ->with(
                Mockery::type(TargetStatistic::class),
                Mockery::subset(['result' => ['total' => 3]])
            );

        $returned = $this->service->ensureAndRecomputeSingle($target, $statistic);

        $this->assertSame($existing, $returned);
        // The push-down path owned the compute (same as the job), with this
        // statistic + target — no second, separate implementation.
        $this->assertCount(1, $target->aggregateCalls);
        $this->assertSame($statistic, $target->aggregateCalls[0][0]);
        $this->assertSame($target, $target->aggregateCalls[0][1]);
    }

    public function test_ensure_and_recompute_single_creates_missing_row_then_computes_and_writes()
    {
        $statistic = new Statistic(['relation' => 'releases']);
        $statistic->id = 9;

        $target = new FakeAggregatingTarget(['total' => 5]);

        $created = new TargetStatistic([
            'target_id' => 1,
            'target_type' => 'year',
            'statistic_id' => 9,
        ]);

        // No row yet — one is created for exactly this (target, statistic) pair.
        $this->targetStatisticRepository->shouldReceive('findForTarget')
            ->once()
            ->with($target, 9)
            ->andReturnNull();
        $this->targetStatisticRepository->shouldReceive('createForTarget')
            ->once()
            ->with($target, ['statistic_id' => 9])
            ->andReturn($created);

        $this->targetStatisticRepository->shouldReceive('update')
            ->once()
            ->with(
                Mockery::type(TargetStatistic::class),
                Mockery::subset(['result' => ['total' => 5]])
            );

        $returned = $this->service->ensureAndRecomputeSingle($target, $statistic);

        $this->assertSame($created, $returned);
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

/**
 * A minimal statistic target that computes via the SQL push-down seam, used to
 * prove ensureAndRecomputeSingle() routes through the SAME aggregateForStatistic
 * path the queued recount uses (and never the in-memory traversal).
 */
class FakeAggregatingTarget extends Model implements CanBeStatisticTargetContract, CanAggregateViaQueryContract
{
    public int $id = 1;

    /** @var array<int, array{0: Statistic, 1: Model}> */
    public array $aggregateCalls = [];

    /** @param array<array-key, int> $result */
    public function __construct(private array $result = ['total' => 0])
    {
        parent::__construct();
    }

    public function morphRelationName(): string
    {
        return 'year';
    }

    public function targetStatistics(): MorphMany
    {
        return $this->morphMany(TargetStatistic::class, 'target');
    }

    public function aggregateForStatistic(Statistic $statistic, Model $target): array
    {
        $this->aggregateCalls[] = [$statistic, $target];

        return $this->result;
    }
}
