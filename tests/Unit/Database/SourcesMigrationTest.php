<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Database;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Polis\Tests\TestCase;

/**
 * Schema-level coverage for
 * 2026_06_19_000001_create_sources_table.
 *
 * `sources` is a new, package-owned table (it doesn't alter a consumer-
 * owned one), so the test can drive the migration directly against the
 * empty in-memory sqlite connection. The assertions cover:
 *
 *   1. up() creates the table with every documented column.
 *   2. The forward (item_type, item_id) morph index exists.
 *   3. The reverse (source, foreign_id) lookup index exists.
 *   4. The natural-key unique constraint on
 *      (item_type, item_id, source, foreign_id) is enforced — this is
 *      what makes setExternalId's updateOrCreate upsert correct.
 *   5. Multiple foreign_ids per (owner, source) ARE allowed (the
 *      common case the trait was built for).
 *   6. down() drops the table cleanly.
 *   7. Re-running up() on a table that already exists is a no-op so a
 *      partial migrate can be safely retried.
 */
final class SourcesMigrationTest extends TestCase
{
    private function loadMigration(): Migration
    {
        $files = glob(__DIR__.'/../../../database/migrations/2026_06_19_000001_create_sources_table.php');
        $this->assertNotEmpty($files, 'Migration file should exist on disk.');

        return require $files[0];
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('sources');
        parent::tearDown();
    }

    public function test_up_creates_table_with_expected_columns(): void
    {
        $this->loadMigration()->up();

        $this->assertTrue(Schema::hasTable('sources'));

        foreach ([
            'id',
            'item_type',
            'item_id',
            'source',
            'foreign_id',
            'url',
            'created_at',
            'updated_at',
            'deleted_at',
        ] as $column) {
            $this->assertTrue(
                Schema::hasColumn('sources', $column),
                "Expected column `{$column}` after up().",
            );
        }
    }

    public function test_up_creates_morph_and_reverse_lookup_indexes(): void
    {
        $this->loadMigration()->up();

        $indexes = collect(DB::select(
            "select name from sqlite_master where type = 'index' and tbl_name = 'sources'"
        ))->pluck('name')->all();

        $this->assertContains(
            'sources_item_morph_index',
            $indexes,
            'Migration must create the (item_type, item_id) morph index.',
        );
        $this->assertContains(
            'sources_source_foreign_id_index',
            $indexes,
            'Migration must create the (source, foreign_id) reverse-lookup index.',
        );
        $this->assertContains(
            'sources_item_source_foreign_id_unique',
            $indexes,
            'Migration must create the natural-key unique constraint.',
        );
    }

    public function test_up_enforces_unique_item_source_foreign_id_tuple(): void
    {
        $this->loadMigration()->up();

        DB::table('sources')->insert([
            'item_type' => 'card_printing',
            'item_id' => 7,
            'source' => 'price_charting',
            'foreign_id' => '3457650',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Same (item_type, item_id, source, foreign_id) — should collide.
        $this->expectException(QueryException::class);
        DB::table('sources')->insert([
            'item_type' => 'card_printing',
            'item_id' => 7,
            'source' => 'price_charting',
            'foreign_id' => '3457650',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_up_allows_multiple_foreign_ids_per_owner_and_source(): void
    {
        $this->loadMigration()->up();

        DB::table('sources')->insert([
            'item_type' => 'card_printing',
            'item_id' => 7,
            'source' => 'price_charting',
            'foreign_id' => '3457650',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('sources')->insert([
            'item_type' => 'card_printing',
            'item_id' => 7,
            'source' => 'price_charting',
            'foreign_id' => '9999999',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertSame(2, DB::table('sources')->count());
    }

    public function test_up_allows_same_foreign_id_across_different_owners(): void
    {
        $this->loadMigration()->up();

        DB::table('sources')->insert([
            'item_type' => 'card_printing',
            'item_id' => 7,
            'source' => 'price_charting',
            'foreign_id' => '3457650',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('sources')->insert([
            'item_type' => 'card_printing',
            'item_id' => 8,
            'source' => 'price_charting',
            'foreign_id' => '3457650',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertSame(2, DB::table('sources')->count());
    }

    public function test_down_drops_the_table(): void
    {
        $migration = $this->loadMigration();
        $migration->up();
        $this->assertTrue(Schema::hasTable('sources'));

        $migration->down();
        $this->assertFalse(Schema::hasTable('sources'));
    }

    public function test_up_is_idempotent_when_table_already_exists(): void
    {
        $migration = $this->loadMigration();
        $migration->up();

        // Second up() must be a no-op — the migration guards on
        // Schema::hasTable so a partial / retried migrate cannot throw.
        $migration->up();

        $this->assertTrue(Schema::hasTable('sources'));
    }
}
