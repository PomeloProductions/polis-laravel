<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Database;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Polis\Tests\TestCase;

/**
 * Schema coverage for
 * 2026_10_10_000001_add_owner_to_statistics_table.
 *
 * The migration adds the polymorphic owner_id/owner_type shape to the
 * `statistics` table. Unlike the articles / article_notes owner migrations
 * there is NO pre-existing single-owner FK to backfill, so the migration is
 * purely additive: nullable owner columns + a composite owner index. A NULL
 * owner means a global/public statistic (the current behaviour), so the
 * change is fully backward-compatible. This test drives up()/down() directly
 * against in-memory sqlite.
 */
final class StatisticsOwnerMigrationTest extends TestCase
{
    private const INDEX_NAME = 'statistics_owner_index';

    private function loadMigration(): Migration
    {
        $files = glob(__DIR__.'/../../../database/migrations/2026_10_10_000001_add_owner_to_statistics_table.php');
        $this->assertNotEmpty($files, 'Migration file should exist on disk.');

        return require $files[0];
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('statistics');
        parent::tearDown();
    }

    private function indexNames(): array
    {
        return array_map(
            static fn (array $i): string => strtolower((string) ($i['name'] ?? '')),
            Schema::getIndexes('statistics'),
        );
    }

    private function createLegacyStatisticsTable(): void
    {
        Schema::create('statistics', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('model');
            $table->string('relation');
            $table->boolean('public')->default(false);
        });
    }

    public function test_up_adds_nullable_owner_columns_and_index(): void
    {
        $this->createLegacyStatisticsTable();

        $this->loadMigration()->up();

        $this->assertTrue(Schema::hasColumn('statistics', 'owner_id'));
        $this->assertTrue(Schema::hasColumn('statistics', 'owner_type'));
        $this->assertContains(self::INDEX_NAME, $this->indexNames());
    }

    public function test_up_leaves_existing_rows_as_global(): void
    {
        $this->createLegacyStatisticsTable();

        DB::table('statistics')->insert([
            ['id' => 1, 'name' => 'Total Articles', 'model' => 'article', 'relation' => 'all', 'public' => true],
        ]);

        $this->loadMigration()->up();

        $row = DB::table('statistics')->where('id', 1)->first();
        // A pre-existing statistic becomes a NULL-owner (global) statistic.
        $this->assertNull($row->owner_id);
        $this->assertNull($row->owner_type);
    }

    public function test_up_allows_owned_statistics(): void
    {
        $this->createLegacyStatisticsTable();

        $this->loadMigration()->up();

        DB::table('statistics')->insert([
            'id' => 1,
            'name' => 'My Year Stats',
            'model' => 'article',
            'relation' => 'all',
            'public' => false,
            'owner_type' => 'user',
            'owner_id' => 42,
        ]);

        $row = DB::table('statistics')->where('id', 1)->first();
        $this->assertSame(42, (int) $row->owner_id);
        $this->assertSame('user', $row->owner_type);
    }

    public function test_up_is_idempotent(): void
    {
        $this->createLegacyStatisticsTable();

        $migration = $this->loadMigration();
        $migration->up();
        // Second up() must not throw on the already-present columns/index.
        $migration->up();

        $this->assertContains(self::INDEX_NAME, $this->indexNames());
    }

    public function test_up_is_noop_when_table_absent(): void
    {
        // A consumer without a statistics table must not error.
        $this->assertFalse(Schema::hasTable('statistics'));

        $this->loadMigration()->up();

        $this->assertFalse(Schema::hasTable('statistics'));
    }

    public function test_down_drops_owner_columns_and_index(): void
    {
        $this->createLegacyStatisticsTable();

        $migration = $this->loadMigration();
        $migration->up();
        $this->assertContains(self::INDEX_NAME, $this->indexNames());

        $migration->down();

        $this->assertNotContains(self::INDEX_NAME, $this->indexNames());
        $this->assertFalse(Schema::hasColumn('statistics', 'owner_type'));
        $this->assertFalse(Schema::hasColumn('statistics', 'owner_id'));
        // The base statistics columns survive down().
        $this->assertTrue(Schema::hasColumn('statistics', 'name'));
    }
}
