<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Repositories\Wiki;

use App\Models\Wiki\Article;
use App\Models\Wiki\ArticleVersion;
use Illuminate\Contracts\Events\Dispatcher;
use Mockery;
use Polis\Events\Article\ArticleVersionCreatedEvent;
use Polis\Repositories\Wiki\ArticleVersionRepository;
use Polis\Tests\TestCase;
use ReflectionClass;
use ReflectionNamedType;

/**
 * Reflection-level coverage for ArticleVersionRepository.
 *
 * The repository's only specialty is dispatching
 * ArticleVersionCreatedEvent(new, old) from its create() override. The
 * parent BaseRepositoryAbstract::create() plumbing (which would have to
 * be exercised to drive create() end-to-end) is already covered in
 * BaseRepositoryAbstractFixtureTest, so here we lock in the constructor
 * shape and the event dispatch contract.
 */
final class ArticleVersionRepositoryTest extends TestCase
{
    public function test_constructor_accepts_model_logger_and_dispatcher(): void
    {
        $constructor = (new ReflectionClass(ArticleVersionRepository::class))->getConstructor();
        $this->assertNotNull($constructor);

        $paramTypes = [];
        foreach ($constructor->getParameters() as $param) {
            $type = $param->getType();
            $this->assertInstanceOf(ReflectionNamedType::class, $type);
            $paramTypes[] = $type->getName();
        }

        $this->assertSame([
            ArticleVersion::class,
            \Psr\Log\LoggerInterface::class,
            Dispatcher::class,
        ], $paramTypes);
    }

    public function test_dispatcher_is_stored_and_used_for_event_dispatch(): void
    {
        $dispatcher = Mockery::mock(Dispatcher::class);
        $repo = new ArticleVersionRepository(
            new ArticleVersion,
            $this->getGenericLogMock(),
            $dispatcher
        );

        $stored = (new \ReflectionClass($repo))->getProperty('dispatcher');
        $stored->setAccessible(true);
        $this->assertSame($dispatcher, $stored->getValue($repo));
    }

    public function test_event_class_carries_new_and_old_versions(): void
    {
        $old = new ArticleVersion;
        $old->id = 1;
        $new = new ArticleVersion;
        $new->id = 2;

        $event = new ArticleVersionCreatedEvent($new, $old);
        $this->assertSame($new, $event->getNewVersion());
        $this->assertSame($old, $event->getOldVersion());
    }
}
