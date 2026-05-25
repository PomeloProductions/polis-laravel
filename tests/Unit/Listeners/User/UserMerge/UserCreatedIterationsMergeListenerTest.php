<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Listeners\User\UserMerge;

use App\Models\User\User;
use App\Models\Wiki\ArticleIteration;
use Illuminate\Support\Collection;
use Polis\Contracts\Repositories\Wiki\ArticleIterationRepositoryContract;
use Polis\Events\User\UserMergeEvent;
use Polis\Listeners\User\UserMerge\UserCreatedIterationsMergeListener;
use Polis\Tests\CustomMockInterface;
use Polis\Tests\TestCase;

/**
 * Class UserCreatedIterationsMergeListenerTest
 */
final class UserCreatedIterationsMergeListenerTest extends TestCase
{
    /**
     * @var ArticleIterationRepositoryContract|CustomMockInterface
     */
    private $repository;

    /**
     * @var UserCreatedIterationsMergeListener
     */
    private $listener;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = mock(ArticleIterationRepositoryContract::class);
        $this->listener = new UserCreatedIterationsMergeListener($this->repository);
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
            'createdIterations' => new Collection([
                new ArticleIteration,
            ]),
        ]);

        $event = new UserMergeEvent($mainUser, $mergeUser, [
            'created_iterations' => true,
        ]);

        $this->repository->shouldReceive('update')->once()->with($mergeUser->createdIterations->first(), [
            'created_by_id' => $mainUser->id,
        ]);

        $this->listener->handle($event);
    }
}
