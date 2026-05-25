<?php

declare(strict_types=1);

namespace Polis\Tests\Integration\Repositories\Vote;

use App\Models\User\User;
use App\Models\Vote\Ballot;
use App\Models\Vote\BallotCompletion;
use App\Models\Vote\Vote;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Polis\Exceptions\NotImplementedException;
use Polis\Repositories\Vote\BallotCompletionRepository;
use Polis\Repositories\Vote\VoteRepository;
use Polis\Tests\DatabaseSetupTrait;
use Polis\Tests\TestCase;
use Polis\Tests\Traits\MocksApplicationLog;

/**
 * Class BallotCompletionRepositoryTest
 */
final class BallotCompletionRepositoryTest extends TestCase
{
    use DatabaseSetupTrait, MocksApplicationLog;

    /**
     * @var BallotCompletionRepository
     */
    protected $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();

        $this->repository = new BallotCompletionRepository(
            new BallotCompletion,
            $this->getGenericLogMock(),
            new VoteRepository(
                new Vote,
                $this->getGenericLogMock(),
            ),
        );
    }

    public function test_find_all_success(): void
    {
        BallotCompletion::factory()->count(5)->create();
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
        $model = BallotCompletion::factory()->create();

        $foundModel = $this->repository->findOrFail($model->id);
        $this->assertEquals($model->id, $foundModel->id);
    }

    public function test_find_or_fail_fails(): void
    {
        BallotCompletion::factory()->create(['id' => 19]);

        $this->expectException(ModelNotFoundException::class);
        $this->repository->findOrFail(20);
    }

    public function test_create_success(): void
    {
        /** @var Ballot $ballot */
        $ballot = Ballot::factory()->create();

        /** @var User $user */
        $user = User::factory()->create();

        /** @var BallotCompletion $ballotCompletion */
        $ballotCompletion = $this->repository->create([
            'user_id' => $user->id,
        ], $ballot);

        $this->assertEquals($ballotCompletion->user_id, $user->id);
        $this->assertEquals($ballotCompletion->ballot_id, $ballot->id);
    }

    public function test_update_throws_exception(): void
    {
        $this->expectException(NotImplementedException::class);
        $this->repository->update(new BallotCompletion, []);
    }

    public function test_delete_success(): void
    {
        $model = BallotCompletion::factory()->create();

        $this->repository->delete($model);

        $this->assertNull(BallotCompletion::find($model->id));
    }
}
