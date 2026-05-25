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
use Polis\Repositories\Vote\BallotRepository;
use Polis\Tests\DatabaseSetupTrait;
use Polis\Tests\TestCase;
use Polis\Tests\Traits\MocksApplicationLog;

/**
 * Class VoteRepositoryTest
 */
final class BallotRepositoryTest extends TestCase
{
    use DatabaseSetupTrait, MocksApplicationLog;

    /**
     * @var BallotRepository
     */
    protected $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();

        $this->repository = new BallotRepository(
            new Ballot,
            $this->getGenericLogMock(),
            new BallotItemRepository(
                new BallotItem,
                $this->getGenericLogMock(),
                new BallotItemOptionRepository(
                    new BallotItemOption,
                    $this->getGenericLogMock(),
                ),
            ),
        );
    }

    public function test_find_all_success(): void
    {
        Ballot::factory()->count(5)->create();
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
        $model = Ballot::factory()->create();

        $foundModel = $this->repository->findOrFail($model->id);
        $this->assertEquals($model->id, $foundModel->id);
    }

    public function test_find_or_fail_fails(): void
    {
        Ballot::factory()->create(['id' => 19]);

        $this->expectException(ModelNotFoundException::class);
        $this->repository->findOrFail(20);
    }

    public function test_create_success(): void
    {
        /** @var Ballot $ballot */
        $ballot = $this->repository->create([
            'type' => Ballot::TYPE_SINGLE_OPTION,
            'ballot_items' => [
                [
                    'ballot_item_options' => [
                        [
                            'subject_id' => ArticleIteration::factory()->create()->id,
                            'subject_type' => 'iteration',
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertEquals($ballot->type, Ballot::TYPE_SINGLE_OPTION);
        $this->assertCount(1, $ballot->ballotItems);
    }

    public function test_update_success(): void
    {
        $model = Ballot::factory()->create();
        $subjects = BallotItem::factory()->count(3)->create([
            'ballot_id' => $model->id,
        ]);

        $this->repository->update($model, [
            'ballot_items' => [
                [
                    'id' => $subjects[1]->id,
                    'ballot_item_options' => [
                        [
                            'subject_id' => ArticleIteration::factory()->create()->id,
                            'subject_type' => 'iteration',
                        ],
                    ],
                ],
                [
                    'ballot_item_options' => [
                        [
                            'subject_id' => ArticleIteration::factory()->create()->id,
                            'subject_type' => 'iteration',
                        ],
                    ],
                ],
            ],
        ]);

        /** @var Ballot $updated */
        $updated = Ballot::find($model->id);
        $this->assertCount(2, $updated->ballotItems);
    }

    public function test_delete_success(): void
    {
        $model = Ballot::factory()->create();

        $this->repository->delete($model);

        $this->assertNull(Ballot::find($model->id));
    }
}
