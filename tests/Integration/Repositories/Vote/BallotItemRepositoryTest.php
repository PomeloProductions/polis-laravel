<?php

declare(strict_types=1);

namespace Polis\Tests\Integration\Repositories\Vote;

use App\Models\Vote\Ballot;
use App\Models\Vote\BallotItem;
use App\Models\Vote\BallotItemOption;
use App\Models\Wiki\ArticleIteration;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Polis\Repositories\Vote\BallotItemOptionRepository;
use Polis\Repositories\Vote\BallotItemRepository;
use Polis\Tests\Application\ApplicationTestCase;
use Polis\Tests\Traits\MocksApplicationLog;

/**
 * Class BallotItemRepositoryTest
 */
final class BallotItemRepositoryTest extends ApplicationTestCase
{
    use MocksApplicationLog;

    /**
     * @var BallotItemRepository
     */
    protected $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();

        $this->repository = new BallotItemRepository(
            new BallotItem,
            $this->getGenericLogMock(),
            new BallotItemOptionRepository(
                new BallotItemOption,
                $this->getGenericLogMock(),
            ),
        );
    }

    public function test_find_all_success(): void
    {
        BallotItem::factory()->count(5)->create();
        $items = $this->repository->findAll();
        $this->assertCount(5, $items);
    }

    public function test_find_all_empty(): void
    {
        $items = $this->repository->findAll();
        $this->assertEmpty($items);
    }

    public function test_find_or_fail_success(): void
    {
        $model = BallotItem::factory()->create();

        $foundModel = $this->repository->findOrFail($model->id);
        $this->assertEquals($model->id, $foundModel->id);
    }

    public function test_find_or_fail_fails(): void
    {
        BallotItem::factory()->create(['id' => 19]);

        $this->expectException(ModelNotFoundException::class);
        $this->repository->findOrFail(20);
    }

    public function test_create_success(): void
    {
        /** @var Ballot $ballot */
        $ballot = Ballot::factory()->create();

        /** @var ArticleIteration $iteration */
        $iteration = ArticleIteration::factory()->create();

        /** @var BallotItem $ballotItem */
        $ballotItem = $this->repository->create([
            'ballot_item_options' => [
                [
                    'subject_id' => $iteration->id,
                    'subject_type' => 'iteration',
                ],
            ],
        ], $ballot);

        $this->assertCount(1, $ballotItem->ballotItemOptions);
        $this->assertEquals($ballotItem->ballotItemOptions[0]->subject_id, $iteration->id);
        $this->assertEquals($ballotItem->ballot_id, $ballot->id);
    }

    public function test_update_success(): void
    {
        $model = BallotItem::factory()->create([
            'votes_cast' => 1,
        ]);
        $this->repository->update($model, [
            'votes_cast' => 5,
        ]);

        /** @var BallotItem $updated */
        $updated = BallotItem::find($model->id);
        $this->assertEquals(5, $updated->votes_cast);
    }

    public function test_delete_success(): void
    {
        $model = BallotItem::factory()->create();

        $this->repository->delete($model);

        $this->assertNull(BallotItem::find($model->id));
    }
}
