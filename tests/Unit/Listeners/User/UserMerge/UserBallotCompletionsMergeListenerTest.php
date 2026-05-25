<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Listeners\User\UserMerge;

use App\Models\User\User;
use App\Models\Vote\BallotCompletion;
use Illuminate\Support\Collection;
use Polis\Contracts\Repositories\Vote\BallotCompletionRepositoryContract;
use Polis\Events\User\UserMergeEvent;
use Polis\Listeners\User\UserMerge\UserBallotCompletionsMergeListener;
use Polis\Tests\CustomMockInterface;
use Polis\Tests\TestCase;

/**
 * Class UserBallotCompletionsMergeListenerTest
 */
final class UserBallotCompletionsMergeListenerTest extends TestCase
{
    /**
     * @var BallotCompletionRepositoryContract|CustomMockInterface
     */
    private $repository;

    /**
     * @var UserBallotCompletionsMergeListener
     */
    private $listener;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = mock(BallotCompletionRepositoryContract::class);
        $this->listener = new UserBallotCompletionsMergeListener($this->repository);
    }

    public function test_handle_without_merge(): void
    {
        $mainUser = new User([
            'email' => 'test@test.com',
        ]);
        $mainUser->id = 564534;

        $mergeUser = new User([
            'email' => 'testy@test.com',
        ]);

        $event = new UserMergeEvent($mainUser, $mergeUser);

        $this->listener->handle($event);
    }

    public function test_handle_with_merge(): void
    {
        $mainUser = new User([
            'email' => 'test@test.com',
        ]);
        $mainUser->id = 564534;

        $mergeUser = new User([
            'email' => 'testy@test.com',
            'ballotCompletions' => new Collection([
                new BallotCompletion,
            ]),
        ]);

        $event = new UserMergeEvent($mainUser, $mergeUser, [
            'ballot_completions' => true,
        ]);

        $this->repository->shouldReceive('update')->once()->with($mergeUser->ballotCompletions->first(), [
            'user_id' => $mainUser->id,
        ]);

        $this->listener->handle($event);
    }
}
