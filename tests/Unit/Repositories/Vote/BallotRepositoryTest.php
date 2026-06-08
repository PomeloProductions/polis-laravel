<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Repositories\Vote;

use App\Models\Vote\Ballot;
use Mockery;
use Polis\Contracts\Repositories\Vote\BallotItemRepositoryContract;
use Polis\Repositories\Vote\BallotRepository;
use Polis\Tests\TestCase;

/**
 * Coverage for BallotRepository — the create/update overrides that
 * delegate child ballot-item syncing to BallotItemRepository via
 * syncChildModels.
 */
final class BallotRepositoryTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        if (! class_exists(Ballot::class, false)) {
            eval('namespace App\\Models\\Vote; class Ballot extends \\Polis\\Models\\BaseModelAbstract {}');
        }
    }

    public function test_create_passes_ballot_items_to_child_repository(): void
    {
        $modelMock = Mockery::mock(Ballot::class);
        $modelMock->shouldReceive('newInstance')->once()->andReturn($modelMock);
        $modelMock->shouldReceive('setAttribute');
        $modelMock->shouldReceive('getAttribute')->andReturn(1);
        $modelMock->wasRecentlyCreated = true;

        $itemRepo = Mockery::mock(BallotItemRepositoryContract::class);
        $itemRepo->shouldReceive('create')
            ->once()
            ->withArgs(function ($data, $parent) use ($modelMock) {
                return $data === ['question' => 'q1'] && $parent === $modelMock;
            });

        $repo = new BallotRepository($modelMock, $this->getGenericLogMock(), $itemRepo);
        $repo->create(['ballot_items' => [['question' => 'q1']]]);
    }

    public function test_create_with_no_ballot_items_does_not_call_item_repo(): void
    {
        $modelMock = Mockery::mock(Ballot::class);
        $modelMock->shouldReceive('newInstance')->once()->andReturn($modelMock);
        $modelMock->shouldReceive('setAttribute');
        $modelMock->shouldReceive('getAttribute')->andReturn(1);
        $modelMock->wasRecentlyCreated = true;

        $itemRepo = Mockery::mock(BallotItemRepositoryContract::class);
        $itemRepo->shouldNotReceive('create');

        $repo = new BallotRepository($modelMock, $this->getGenericLogMock(), $itemRepo);
        $repo->create();
    }

    public function test_update_with_ballot_items_syncs_with_existing(): void
    {
        // Use a concrete BaseModelAbstract instance so the
        // Collection->firstWhere/contains/pluck lookups inside
        // syncChildModels can iterate properly.
        $existingItem = new Ballot;
        $existingItem->id = 50;

        $modelMock = Mockery::mock(Ballot::class);
        $modelMock->shouldReceive('update')->once()->andReturn(true);
        $modelMock->shouldReceive('setAttribute');
        // syncChildModels reads $model->ballotItems
        $modelMock->shouldReceive('getAttribute')->with('ballotItems')->andReturn(new \Illuminate\Support\Collection([$existingItem]));
        $modelMock->shouldReceive('getAttribute')->andReturn(1);

        $itemRepo = Mockery::mock(BallotItemRepositoryContract::class);
        // existing item not in the new data -> delete
        $itemRepo->shouldReceive('delete')->once()->with($existingItem);
        // new item -> create
        $itemRepo->shouldReceive('create')
            ->once()
            ->with(['question' => 'new'], $modelMock);

        $repo = new BallotRepository($modelMock, $this->getGenericLogMock(), $itemRepo);
        $repo->update($modelMock, ['ballot_items' => [['question' => 'new']]]);
    }

    public function test_update_without_ballot_items_skips_child_sync(): void
    {
        $modelMock = Mockery::mock(Ballot::class);
        $modelMock->shouldReceive('update')->once()->andReturn(true);
        $modelMock->shouldReceive('setAttribute');
        $modelMock->shouldReceive('getAttribute')->andReturn(1);

        $itemRepo = Mockery::mock(BallotItemRepositoryContract::class);
        $itemRepo->shouldNotReceive('create');
        $itemRepo->shouldNotReceive('delete');

        $repo = new BallotRepository($modelMock, $this->getGenericLogMock(), $itemRepo);
        $repo->update($modelMock, ['name' => 'x']);
    }

    public function test_sync_child_models_updates_existing_when_id_matches(): void
    {
        // Use a real Ballot instance (concrete BaseModelAbstract) so the
        // Collection->firstWhere('id', $id) lookup — which internally
        // calls offsetExists on Eloquent — works without explicit stubs.
        $existingItem = new Ballot;
        $existingItem->id = 50;

        $modelMock = Mockery::mock(Ballot::class);
        $modelMock->shouldReceive('update')->once()->andReturn(true);
        $modelMock->shouldReceive('setAttribute');
        $modelMock->shouldReceive('getAttribute')->with('ballotItems')->andReturn(new \Illuminate\Support\Collection([$existingItem]));
        $modelMock->shouldReceive('getAttribute')->andReturn(1);

        $itemRepo = Mockery::mock(BallotItemRepositoryContract::class);
        $itemRepo->shouldReceive('update')
            ->once()
            ->with($existingItem, ['id' => 50, 'question' => 'updated']);

        $repo = new BallotRepository($modelMock, $this->getGenericLogMock(), $itemRepo);
        $repo->update($modelMock, ['ballot_items' => [['id' => 50, 'question' => 'updated']]]);
    }
}
