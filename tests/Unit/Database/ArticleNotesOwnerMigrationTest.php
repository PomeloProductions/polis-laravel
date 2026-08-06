<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Database;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Polis\Tests\TestCase;

/**
 * Schema + data coverage for
 * 2026_08_06_000003_add_owner_to_article_notes_table.
 */
final class ArticleNotesOwnerMigrationTest extends TestCase
{
    private const INDEX_NAME = 'article_notes_owner_index';

    private function loadMigration(): Migration
    {
        $files = glob(__DIR__.'/../../../database/migrations/2026_08_06_000003_add_owner_to_article_notes_table.php');
        $this->assertNotEmpty($files, 'Migration file should exist on disk.');

        return require $files[0];
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('article_notes');
        parent::tearDown();
    }

    private function indexNames(): array
    {
        return array_map(
            static fn (array $i): string => strtolower((string) ($i['name'] ?? '')),
            Schema::getIndexes('article_notes'),
        );
    }

    private function createLegacyTable(): void
    {
        Schema::create('article_notes', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('article_id');
        });
    }

    public function test_up_adds_owner_columns_and_index_and_retains_user_id(): void
    {
        $this->createLegacyTable();

        $this->loadMigration()->up();

        $this->assertTrue(Schema::hasColumn('article_notes', 'owner_id'));
        $this->assertTrue(Schema::hasColumn('article_notes', 'owner_type'));
        $this->assertTrue(Schema::hasColumn('article_notes', 'user_id'));
        $this->assertContains(self::INDEX_NAME, $this->indexNames());
    }

    public function test_up_backfills_owner_from_user_id(): void
    {
        $this->createLegacyTable();
        DB::table('article_notes')->insert(['id' => 1, 'user_id' => 8, 'article_id' => 3]);

        $this->loadMigration()->up();

        $row = DB::table('article_notes')->where('id', 1)->first();
        $this->assertSame(8, (int) $row->owner_id);
        $this->assertSame('user', $row->owner_type);
    }

    public function test_up_is_idempotent(): void
    {
        $this->createLegacyTable();

        $migration = $this->loadMigration();
        $migration->up();
        $migration->up();

        $this->assertContains(self::INDEX_NAME, $this->indexNames());
    }

    public function test_down_drops_owner_columns_but_keeps_user_id(): void
    {
        $this->createLegacyTable();

        $migration = $this->loadMigration();
        $migration->up();
        $migration->down();

        $this->assertNotContains(self::INDEX_NAME, $this->indexNames());
        $this->assertFalse(Schema::hasColumn('article_notes', 'owner_id'));
        $this->assertTrue(Schema::hasColumn('article_notes', 'user_id'));
    }
}
