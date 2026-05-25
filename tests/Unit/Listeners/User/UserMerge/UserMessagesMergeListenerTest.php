<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Listeners\User\UserMerge;

use App\Models\Messaging\Message;
use App\Models\User\User;
use Illuminate\Support\Collection;
use Polis\Contracts\Repositories\Messaging\MessageRepositoryContract;
use Polis\Events\User\UserMergeEvent;
use Polis\Listeners\User\UserMerge\UserMessagesMergeListener;
use Polis\Tests\CustomMockInterface;
use Polis\Tests\TestCase;

/**
 * Class UserMessagesMergeListenerTest
 */
final class UserMessagesMergeListenerTest extends TestCase
{
    /**
     * @var MessageRepositoryContract|CustomMockInterface
     */
    private $repository;

    /**
     * @var UserMessagesMergeListener
     */
    private $listener;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = mock(MessageRepositoryContract::class);
        $this->listener = new UserMessagesMergeListener($this->repository);
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
            'messages' => new Collection([
                new Message,
            ]),
        ]);

        $event = new UserMergeEvent($mainUser, $mergeUser, [
            'messages' => true,
        ]);

        $this->repository->shouldReceive('update')->once()->with($mergeUser->messages->first(), [
            'user_id' => $mainUser->id,
        ]);

        $this->listener->handle($event);
    }
}
