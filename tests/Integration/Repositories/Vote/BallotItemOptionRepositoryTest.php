<?php

declare(strict_types=1);

namespace Polis\Tests\Integration\Repositories\BallotItemOption;

use App\Models\User\User;
use App\Models\Vote\BallotItem;
use App\Models\Vote\BallotItemOption;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Polis\Repositories\Vote\BallotItemOptionRepository;
use Polis\Tests\Application\ApplicationTestCase;
use Polis\Tests\Traits\MocksApplicationLog;

/**
 * Class BallotItemOptionRepositoryTest
 */
final class BallotItemOptionRepositoryTest extends ApplicationTestCase
{
    use MocksApplicationLog;

    /**
     * @var BallotItemOptionRepository
     */
    protected $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();

        $this->repository = new BallotItemOptionRepository(
            new BallotItemOption,
            $this->getGenericLogMock()
        );
    }

    public function test_find_all_success(): void
    {
        BallotItemOption::factory()->count(5)->create();
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
        $model = BallotItemOption::factory()->create();

        $foundModel = $this->repository->findOrFail($model->id);
        $this->assertEquals($model->id, $foundModel->id);
    }

    public function test_find_or_fail_fails(): void
    {
        BallotItemOption::factory()->create(['id' => 19]);

        $this->expectException(ModelNotFoundException::class);
        $this->repository->findOrFail(20);
    }

    public function test_create_success(): void
    {
        /** @var BallotItem $ballotItem */
        $ballotItem = BallotItem::factory()->create();

        /** @var User $user */
        $user = User::factory()->create();

        /** @var BallotItemOption $ballotItemOption */
        $ballotItemOption = $this->repository->create([
            'subject_id' => $user->id,
            'subject_type' => 'user',
        ], $ballotItem);

        $this->assertEquals($user->id, $ballotItemOption->subject_id);
        $this->assertEquals('user', $ballotItemOption->subject_type);
        $this->assertEquals($ballotItem->id, $ballotItemOption->ballot_item_id);
    }

    public function test_update_success(): void
    {
        $model = BallotItemOption::factory()->create([
            'vote_count' => 1,
        ]);
        $this->repository->update($model, [
            'vote_count' => 2,
        ]);

        /** @var BallotItemOption $updated */
        $updated = BallotItemOption::find($model->id);
        $this->assertEquals(2, $updated->vote_count);
    }

    public function test_delete_success(): void
    {
        $model = BallotItemOption::factory()->create();

        $this->repository->delete($model);

        $this->assertNull(BallotItemOption::find($model->id));
    }
}
