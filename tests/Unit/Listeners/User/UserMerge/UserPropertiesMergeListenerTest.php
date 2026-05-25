<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Listeners\User\UserMerge;

use App\Models\Subscription\Subscription;
use App\Models\User\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Polis\Contracts\Repositories\User\UserRepositoryContract;
use Polis\Events\User\UserMergeEvent;
use Polis\Listeners\User\UserMerge\UserPropertiesMergeListener;
use Polis\Tests\CustomMockInterface;
use Polis\Tests\TestCase;

/**
 * Class UserPropertiesMergeListenerTest
 */
final class UserPropertiesMergeListenerTest extends TestCase
{
    /**
     * @var UserRepositoryContract|CustomMockInterface
     */
    private $repository;

    /**
     * @var UserPropertiesMergeListener
     */
    private $listener;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = mock(UserRepositoryContract::class);
        $this->listener = new UserPropertiesMergeListener($this->repository);
    }

    public function test_handle_without_options(): void
    {
        $mainUser = new User([
            'email' => 'test@test.com',
        ]);
        $mainUser->id = 564534;

        $mergeUser = new User([
            'email' => 'testy@test.com',
        ]);

        $now = Carbon::now();
        Carbon::setTestNow($now);

        $this->repository->shouldReceive('update')->once()->with($mergeUser, [
            'merged_to_id' => $mainUser->id,
            'deleted_at' => $now,
        ]);

        $event = new UserMergeEvent($mainUser, $mergeUser);

        $this->listener->handle($event);
    }

    public function test_handle_with_options(): void
    {
        $mainUser = new User([
            'email' => 'test@test.com',
            'subscriptions' => new Collection([
                new Subscription,
            ]),
        ]);
        $mainUser->id = 564534;

        $mergeUser = new User([
            'email' => 'testy@test.com',
        ]);

        $now = Carbon::now();
        Carbon::setTestNow($now);

        $this->repository->shouldReceive('update')->once()->with($mainUser, [
            'email' => 'testy@test.com',
        ]);

        $this->repository->shouldReceive('update')->once()->with($mergeUser, [
            'merged_to_id' => $mainUser->id,
            'deleted_at' => $now,
        ]);

        $event = new UserMergeEvent($mainUser, $mergeUser, [
            'email' => true,
            'subscriptions' => true,
        ]);

        $this->listener->handle($event);
    }
}
