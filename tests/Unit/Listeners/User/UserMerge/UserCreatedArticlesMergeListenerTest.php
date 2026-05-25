<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Listeners\User\UserMerge;

use App\Models\User\User;
use App\Models\Wiki\Article;
use Illuminate\Support\Collection;
use Polis\Contracts\Repositories\Wiki\ArticleRepositoryContract;
use Polis\Events\User\UserMergeEvent;
use Polis\Listeners\User\UserMerge\UserCreatedArticlesMergeListener;
use Polis\Tests\CustomMockInterface;
use Polis\Tests\TestCase;

/**
 * Class UserCreatedArticlesMergeListenerTest
 */
final class UserCreatedArticlesMergeListenerTest extends TestCase
{
    /**
     * @var ArticleRepositoryContract|CustomMockInterface
     */
    private $repository;

    /**
     * @var UserCreatedArticlesMergeListener
     */
    private $listener;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = mock(ArticleRepositoryContract::class);
        $this->listener = new UserCreatedArticlesMergeListener($this->repository);
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
            'createdArticles' => new Collection([
                new Article,
            ]),
        ]);

        $event = new UserMergeEvent($mainUser, $mergeUser, [
            'created_articles' => true,
        ]);

        $this->repository->shouldReceive('update')->once()->with($mergeUser->createdArticles->first(), [
            'created_by_id' => $mainUser->id,
        ]);

        $this->listener->handle($event);
    }
}
