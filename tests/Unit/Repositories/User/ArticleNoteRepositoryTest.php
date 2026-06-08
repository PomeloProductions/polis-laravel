<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Repositories\User;

use App\Models\User\ArticleNote;
use Illuminate\Contracts\Events\Dispatcher;
use Mockery;
use Polis\Events\User\ArticleNoteCompletedEvent;
use Polis\Repositories\User\ArticleNoteRepository;
use Polis\Tests\TestCase;

/**
 * Coverage for ArticleNoteRepository — the completed/uncompleted state
 * transitions on create() and update() and the corresponding
 * ArticleNoteCompletedEvent dispatch on the create-or-mark-completed path.
 */
final class ArticleNoteRepositoryTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        if (! class_exists(ArticleNote::class, false)) {
            class_alias(
                \Polis\Models\BaseModelAbstract::class,
                ArticleNote::class,
            );
        }
    }

    private function buildModelMock()
    {
        $mock = Mockery::mock(ArticleNote::class);
        $mock->shouldReceive('setAttribute');
        $mock->shouldReceive('getAttribute')->andReturn(1);
        $mock->wasRecentlyCreated = true;

        return $mock;
    }

    public function test_create_with_completed_true_sets_completed_at_and_dispatches_event(): void
    {
        $modelMock = $this->buildModelMock();
        $modelMock->shouldReceive('newInstance')
            ->once()
            ->andReturnUsing(function ($data) use ($modelMock) {
                $this->assertArrayHasKey('completed_at', $data);
                $this->assertNotNull($data['completed_at']);

                return $modelMock;
            });

        $dispatcher = Mockery::mock(Dispatcher::class);
        $dispatcher->shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($e) => $e instanceof ArticleNoteCompletedEvent);

        $repo = new ArticleNoteRepository($modelMock, $this->getGenericLogMock(), $dispatcher);
        $repo->create(['completed' => true]);
    }

    public function test_create_without_completed_does_not_dispatch_event(): void
    {
        $modelMock = $this->buildModelMock();
        $modelMock->shouldReceive('newInstance')
            ->once()
            ->andReturnUsing(function ($data) use ($modelMock) {
                $this->assertArrayNotHasKey('completed_at', $data);

                return $modelMock;
            });

        $dispatcher = Mockery::mock(Dispatcher::class);
        $dispatcher->shouldNotReceive('dispatch');

        $repo = new ArticleNoteRepository($modelMock, $this->getGenericLogMock(), $dispatcher);
        $repo->create();
    }

    public function test_update_transitioning_to_completed_dispatches_event(): void
    {
        $modelMock = Mockery::mock(ArticleNote::class);
        $modelMock->shouldReceive('setAttribute');
        $modelMock->shouldReceive('getAttribute')->with('completed_at')->andReturn(null);
        $modelMock->shouldReceive('getAttribute')->andReturn(1);
        $modelMock->wasRecentlyCreated = true;
        $modelMock->shouldReceive('update')->once()->andReturn(true);

        $dispatcher = Mockery::mock(Dispatcher::class);
        $dispatcher->shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($e) => $e instanceof ArticleNoteCompletedEvent);

        $repo = new ArticleNoteRepository($modelMock, $this->getGenericLogMock(), $dispatcher);
        $repo->update($modelMock, ['completed' => true]);
    }

    public function test_update_when_already_completed_does_not_dispatch_event_again(): void
    {
        $modelMock = Mockery::mock(ArticleNote::class);
        $modelMock->shouldReceive('setAttribute');
        $modelMock->shouldReceive('getAttribute')->with('completed_at')->andReturn(now());
        $modelMock->shouldReceive('getAttribute')->andReturn(1);
        $modelMock->wasRecentlyCreated = true;
        $modelMock->shouldReceive('update')->once()->andReturn(true);

        $dispatcher = Mockery::mock(Dispatcher::class);
        $dispatcher->shouldNotReceive('dispatch');

        $repo = new ArticleNoteRepository($modelMock, $this->getGenericLogMock(), $dispatcher);
        $repo->update($modelMock, ['completed' => true]);
    }

    public function test_update_setting_completed_to_false_clears_completed_at_without_event(): void
    {
        $modelMock = Mockery::mock(ArticleNote::class);
        $modelMock->shouldReceive('setAttribute');
        $modelMock->shouldReceive('getAttribute')->with('completed_at')->andReturn(now());
        $modelMock->shouldReceive('getAttribute')->andReturn(1);
        $modelMock->wasRecentlyCreated = true;
        $modelMock->shouldReceive('update')
            ->once()
            ->andReturnUsing(function ($data) {
                $this->assertNull($data['completed_at']);

                return true;
            });

        $dispatcher = Mockery::mock(Dispatcher::class);
        $dispatcher->shouldNotReceive('dispatch');

        $repo = new ArticleNoteRepository($modelMock, $this->getGenericLogMock(), $dispatcher);
        $repo->update($modelMock, ['completed' => false]);
    }
}
