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
 * 2026_08_06_000002_add_owner_to_user_pages_table.
 */
final class UserPagesOwnerMigrationTest extends TestCase
{
    private const INDEX_NAME = 'user_pages_owner_index';

    private function loadMigration(): Migration
    {
        $files = glob(__DIR__.'/../../../database/migrations/2026_08_06_000002_add_owner_to_user_pages_table.php');
        $this->assertNotEmpty($files, 'Migration file should exist on disk.');

        return require $files[0];
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('user_pages');
        parent::tearDown();
    }

    private function indexNames(): array
    {
        return array_map(
            static fn (array $i): string => strtolower((string) ($i['name'] ?? '')),
            Schema::getIndexes('user_pages'),
        );
    }

    private function createLegacyTable(): void
    {
        Schema::create('user_pages', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('name');
        });
    }

    public function test_up_adds_owner_columns_and_index_and_retains_user_id(): void
    {
        $this->createLegacyTable();

        $this->loadMigration()->up();

        $this->assertTrue(Schema::hasColumn('user_pages', 'owner_id'));
        $this->assertTrue(Schema::hasColumn('user_pages', 'owner_type'));
        $this->assertTrue(Schema::hasColumn('user_pages', 'user_id'));
        $this->assertContains(self::INDEX_NAME, $this->indexNames());
    }

    public function test_up_backfills_owner_from_user_id(): void
    {
        $this->createLegacyTable();
        DB::table('user_pages')->insert(['id' => 1, 'user_id' => 12, 'name' => 'Home']);

        $this->loadMigration()->up();

        $row = DB::table('user_pages')->where('id', 1)->first();
        $this->assertSame(12, (int) $row->owner_id);
        $this->assertSame('user', $row->owner_type);
        $this->assertSame(12, (int) $row->user_id);
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
        $this->assertFalse(Schema::hasColumn('user_pages', 'owner_id'));
        $this->assertFalse(Schema::hasColumn('user_pages', 'owner_type'));
        $this->assertTrue(Schema::hasColumn('user_pages', 'user_id'));
    }
}
