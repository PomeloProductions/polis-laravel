<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Events\Article;

use App\Models\Wiki\ArticleVersion;
use Polis\Events\Article\ArticleVersionCreatedEvent;
use Polis\Tests\TestCase;

/**
 * Class ArticleVersionCreatedEventTest
 */
final class ArticleVersionCreatedEventTest extends TestCase
{
    public function test_get_new_version(): void
    {
        $newVersion = new ArticleVersion;
        $newVersion->id = 455;
        $oldVersion = new ArticleVersion;
        $oldVersion->id = 346;

        $event = new ArticleVersionCreatedEvent($newVersion, $oldVersion);

        $this->assertEquals($newVersion, $event->getNewVersion());
    }

    public function test_get_old_version(): void
    {
        $newVersion = new ArticleVersion;
        $newVersion->id = 455;
        $oldVersion = new ArticleVersion;
        $oldVersion->id = 346;

        $event = new ArticleVersionCreatedEvent($newVersion, $oldVersion);

        $this->assertEquals($oldVersion, $event->getOldVersion());
    }
}
