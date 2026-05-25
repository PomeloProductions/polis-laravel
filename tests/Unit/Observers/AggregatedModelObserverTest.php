<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Observers;

use App\Models\Collection\Collection;
use App\Models\Collection\CollectionItem;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Polis\Contracts\Services\Relations\RelationTraversalServiceContract;
use Polis\Jobs\Statistic\ProcessTargetStatisticsJob;
use Polis\Observers\AggregatedModelObserver;
use Polis\Tests\TestCase;

class AggregatedModelObserverTest extends TestCase
{
    private RelationTraversalServiceContract|MockInterface $relationTraversalService;

    private Dispatcher|MockInterface $dispatcher;

    private AggregatedModelObserver $observer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->relationTraversalService = Mockery::mock(RelationTraversalServiceContract::class);
        $this->dispatcher = Mockery::mock(Dispatcher::class);
        $this->observer = new AggregatedModelObserver(
            $this->relationTraversalService,
            $this->dispatcher
        );
    }

    #[DataProvider('modelEventProvider')]
    public function test_model_events_dispatch_job_for_statistic_target(string $event): void
    {
        $collection = new Collection([
            'id' => 1,
            'name' => 'Test Collection',
            'owner_id' => 1,
            'owner_type' => 'user',
            'is_public' => true,
        ]);

        $collectionItem = new CollectionItem([
            'id' => 1,
            'collection_id' => 1,
            'item_id' => 1,
            'item_type' => 'article',
            'order' => 1,
        ]);

        $this->relationTraversalService->shouldReceive('traverseRelations')
            ->with($collectionItem, 'collection')
            ->andReturn(new EloquentCollection([$collection]));

        $this->dispatcher->shouldReceive('dispatch')
            ->with(Mockery::type(ProcessTargetStatisticsJob::class))
            ->once();

        $this->observer->$event($collectionItem);
    }

    #[DataProvider('modelEventProvider')]
    public function test_model_events_do_not_dispatch_job_for_non_statistic_target(string $event): void
    {
        $collectionItem = new CollectionItem([
            'id' => 1,
            'collection_id' => 1,
            'item_id' => 1,
            'item_type' => 'article',
            'order' => 1,
        ]);

        $this->relationTraversalService->shouldReceive('traverseRelations')
            ->with($collectionItem, 'collection')
            ->andReturn(new EloquentCollection([new \stdClass]));

        $this->dispatcher->shouldNotReceive('dispatch');

        $this->observer->$event($collectionItem);
    }

    public static function modelEventProvider(): array
    {
        return [
            'created event' => ['created'],
            'updated event' => ['updated'],
            'deleted event' => ['deleted'],
        ];
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
