<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Repositories\Vote;

use App\Models\Vote\BallotItem;
use Mockery;
use Polis\Contracts\Repositories\Vote\BallotItemOptionRepositoryContract;
use Polis\Repositories\Vote\BallotItemRepository;
use Polis\Tests\TestCase;

/**
 * Coverage for BallotItemRepository — the create/update overrides that
 * sync ballot_item_options via BallotItemOptionRepository.
 */
final class BallotItemRepositoryTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        if (! class_exists(BallotItem::class, false)) {
            eval('namespace App\\Models\\Vote; class BallotItem extends \\Polis\\Models\\BaseModelAbstract {}');
        }
    }

    public function test_create_passes_options_to_option_repository(): void
    {
        $modelMock = Mockery::mock(BallotItem::class);
        $modelMock->shouldReceive('newInstance')->once()->andReturn($modelMock);
        $modelMock->shouldReceive('setAttribute');
        $modelMock->shouldReceive('getAttribute')->andReturn(1);
        $modelMock->wasRecentlyCreated = true;

        $optionRepo = Mockery::mock(BallotItemOptionRepositoryContract::class);
        $optionRepo->shouldReceive('create')->once()->with(['label' => 'Y'], $modelMock);

        $repo = new BallotItemRepository($modelMock, $this->getGenericLogMock(), $optionRepo);
        $repo->create(['ballot_item_options' => [['label' => 'Y']]]);
    }

    public function test_update_with_options_calls_sync_child_models(): void
    {
        $existing = new BallotItem;
        $existing->id = 10;

        $modelMock = Mockery::mock(BallotItem::class);
        $modelMock->shouldReceive('update')->once()->andReturn(true);
        $modelMock->shouldReceive('setAttribute');
        $modelMock->shouldReceive('getAttribute')
            ->with('ballotItemOptions')
            ->andReturn(new \Illuminate\Support\Collection([$existing]));
        $modelMock->shouldReceive('getAttribute')->andReturn(1);

        $optionRepo = Mockery::mock(BallotItemOptionRepositoryContract::class);
        $optionRepo->shouldReceive('delete')->once()->with($existing);
        $optionRepo->shouldReceive('create')->once()->with(['label' => 'N'], $modelMock);

        $repo = new BallotItemRepository($modelMock, $this->getGenericLogMock(), $optionRepo);
        $repo->update($modelMock, ['ballot_item_options' => [['label' => 'N']]]);
    }

    public function test_update_without_options_does_not_sync(): void
    {
        $modelMock = Mockery::mock(BallotItem::class);
        $modelMock->shouldReceive('update')->once()->andReturn(true);
        $modelMock->shouldReceive('setAttribute');
        $modelMock->shouldReceive('getAttribute')->andReturn(1);

        $optionRepo = Mockery::mock(BallotItemOptionRepositoryContract::class);
        $optionRepo->shouldNotReceive('create');
        $optionRepo->shouldNotReceive('delete');

        $repo = new BallotItemRepository($modelMock, $this->getGenericLogMock(), $optionRepo);
        $repo->update($modelMock, ['label' => 'unchanged']);
    }
}
