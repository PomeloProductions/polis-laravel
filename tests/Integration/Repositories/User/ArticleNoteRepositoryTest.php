<?php

declare(strict_types=1);

namespace Polis\Tests\Integration\Repositories\User;

use App\Models\User\ArticleNote;
use App\Models\User\User;
use App\Models\Wiki\Article;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Event;
use Polis\Events\User\ArticleNoteCompletedEvent;
use Polis\Repositories\User\ArticleNoteRepository;
use Polis\Tests\Application\ApplicationTestCase;
use Polis\Tests\Traits\MocksApplicationLog;

/**
 * Class ArticleNoteRepositoryTest
 */
final class ArticleNoteRepositoryTest extends ApplicationTestCase
{
    use MocksApplicationLog;

    /**
     * @var ArticleNoteRepository
     */
    protected $repository;

    /**
     * @var Dispatcher
     */
    protected $dispatcher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();

        $this->dispatcher = $this->app->make(Dispatcher::class);

        $this->repository = new ArticleNoteRepository(
            new ArticleNote,
            $this->getGenericLogMock(),
            $this->dispatcher,
        );
    }

    public function test_find_all_success(): void
    {
        ArticleNote::factory()->count(5)->create();
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
        $model = ArticleNote::factory()->create();

        $foundModel = $this->repository->findOrFail($model->id);
        $this->assertEquals($model->id, $foundModel->id);
    }

    public function test_find_or_fail_fails(): void
    {
        ArticleNote::factory()->create(['id' => 19]);

        $this->expectException(ModelNotFoundException::class);
        $this->repository->findOrFail(20);
    }

    public function test_create_success(): void
    {
        Event::fake([ArticleNoteCompletedEvent::class]);

        /** @var User $user */
        $user = User::factory()->create();

        /** @var Article $article */
        $article = Article::factory()->create();

        /** @var ArticleNote $articleNote */
        $articleNote = $this->repository->create([
            'article_id' => $article->id,
            'response' => 'A response',
        ], $user);

        $this->assertEquals($articleNote->user_id, $user->id);
        $this->assertEquals($articleNote->article_id, $article->id);
        $this->assertEquals('A response', $articleNote->response);
        $this->assertNull($articleNote->completed_at);

        // Assert the event was NOT dispatched (not completed)
        Event::assertNotDispatched(ArticleNoteCompletedEvent::class);
    }

    public function test_create_success_with_completed(): void
    {
        Event::fake([ArticleNoteCompletedEvent::class]);

        // Recreate repository after faking events so it uses the fake dispatcher
        $repository = new ArticleNoteRepository(
            new ArticleNote,
            $this->getGenericLogMock(),
            $this->app->make(Dispatcher::class),
        );

        /** @var User $user */
        $user = User::factory()->create();
        $user->roles()->attach(5); // Attendee role

        /** @var Article $article */
        $article = Article::factory()->create();

        /** @var ArticleNote $articleNote */
        $articleNote = $repository->create([
            'article_id' => $article->id,
            'completed' => true,
            'response' => 'Test response content',
        ], $user);

        $this->assertEquals($articleNote->user_id, $user->id);
        $this->assertEquals($articleNote->article_id, $article->id);
        $this->assertNotNull($articleNote->completed_at);

        // Assert the event was dispatched
        Event::assertDispatched(ArticleNoteCompletedEvent::class, function ($event) use ($articleNote) {
            return $event->getArticleNote()->id === $articleNote->id;
        });
    }

    public function test_update_success(): void
    {
        Event::fake([ArticleNoteCompletedEvent::class]);

        // Recreate repository after faking events so it uses the fake dispatcher
        $repository = new ArticleNoteRepository(
            new ArticleNote,
            $this->getGenericLogMock(),
            $this->app->make(Dispatcher::class),
        );

        /** @var User $user */
        $user = User::factory()->create();
        $user->roles()->attach(6); // Virtual role

        $model = ArticleNote::factory()->create([
            'user_id' => $user->id,
            'response' => 'Original response',
            'completed_at' => null,
        ]);

        $updated = $repository->update($model, [
            'response' => 'Updated response',
            'completed' => true,
        ]);

        $this->assertEquals('Updated response', $updated->response);
        $this->assertNotNull($updated->completed_at);

        // Assert the event was dispatched
        Event::assertDispatched(ArticleNoteCompletedEvent::class, function ($event) use ($updated) {
            return $event->getArticleNote()->id === $updated->id;
        });
    }

    public function test_update_success_unmark_completed(): void
    {
        Event::fake([ArticleNoteCompletedEvent::class]);

        $model = ArticleNote::factory()->create([
            'completed_at' => now(),
        ]);

        $updated = $this->repository->update($model, [
            'completed' => false,
        ]);

        $this->assertNull($updated->completed_at);

        // Assert the event was NOT dispatched (unmarking completion should not trigger the event)
        Event::assertNotDispatched(ArticleNoteCompletedEvent::class);
    }

    public function test_update_already_completed_does_not_fire_event(): void
    {
        Event::fake([ArticleNoteCompletedEvent::class]);

        // Create an already completed article note
        $model = ArticleNote::factory()->create([
            'completed_at' => now(),
            'response' => 'Original response',
        ]);

        // Update with completed still true
        $updated = $this->repository->update($model, [
            'response' => 'Updated response',
            'completed' => true,
        ]);

        $this->assertEquals('Updated response', $updated->response);
        $this->assertNotNull($updated->completed_at);

        // Assert the event was NOT dispatched (already completed, so no transition)
        Event::assertNotDispatched(ArticleNoteCompletedEvent::class);
    }

    public function test_delete_success(): void
    {
        $model = ArticleNote::factory()->create();

        $this->repository->delete($model);

        $this->assertNull(ArticleNote::find($model->id));
    }
}
