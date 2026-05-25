<?php

declare(strict_types=1);

namespace Polis\Tests\Integration\Repositories\Vote;

use App\Models\Vote\BallotCompletion;
use App\Models\Vote\BallotItemOption;
use App\Models\Vote\Vote;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Polis\Repositories\Vote\VoteRepository;
use Polis\Tests\DatabaseSetupTrait;
use Polis\Tests\TestCase;
use Polis\Tests\Traits\MocksApplicationLog;

/**
 * Class VoteRepositoryTest
 */
final class VoteRepositoryTest extends TestCase
{
    use DatabaseSetupTrait, MocksApplicationLog;

    /**
     * @var VoteRepository
     */
    protected $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();

        $this->repository = new VoteRepository(
            new Vote,
            $this->getGenericLogMock()
        );
    }

    public function test_find_all_success(): void
    {
        Vote::factory()->count(5)->create();
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
        $model = Vote::factory()->create();

        $foundModel = $this->repository->findOrFail($model->id);
        $this->assertEquals($model->id, $foundModel->id);
    }

    public function test_find_or_fail_fails(): void
    {
        Vote::factory()->create(['id' => 19]);

        $this->expectException(ModelNotFoundException::class);
        $this->repository->findOrFail(20);
    }

    public function test_create_success(): void
    {
        /** @var BallotCompletion $ballotCompletion */
        $ballotCompletion = BallotCompletion::factory()->create();

        /** @var BallotItemOption $ballotItemOption */
        $ballotItemOption = BallotItemOption::factory()->create();

        /** @var Vote $vote */
        $vote = $this->repository->create([
            'ballot_item_option_id' => $ballotItemOption->id,
            'result' => 1,
        ], $ballotCompletion);

        $this->assertEquals(1, $vote->result);
        $this->assertEquals($ballotItemOption->id, $vote->ballot_item_option_id);
        $this->assertEquals($ballotCompletion->id, $vote->ballot_completion_id);
    }

    public function test_update_success(): void
    {
        $model = Vote::factory()->create([
            'result' => 1,
        ]);
        $this->repository->update($model, [
            'result' => 0,
        ]);

        /** @var Vote $updated */
        $updated = Vote::find($model->id);
        $this->assertEquals(0, $updated->result);
    }

    public function test_delete_success(): void
    {
        $model = Vote::factory()->create();

        $this->repository->delete($model);

        $this->assertNull(Vote::find($model->id));
    }
}
