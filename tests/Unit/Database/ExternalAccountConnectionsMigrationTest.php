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
 * 2026_06_08_000001_create_external_account_connections_table.
 *
 * The migration is owned wholly by polis-laravel (it creates a new table
 * rather than altering a consumer-owned one), so we can run it against the
 * empty in-memory sqlite connection without first stubbing parent tables.
 *
 * What we verify:
 *   1. up() creates the table with every documented column.
 *   2. The composite unique (user_id, provider) is enforced.
 *   3. The provider+token_expires_at index exists for the refresh-scheduler
 *      lookup path.
 *   4. down() drops the table cleanly.
 *   5. Re-running up() on a table that already exists is a no-op (idempotent),
 *      so a partial migrate failure can be safely retried.
 */
final class ExternalAccountConnectionsMigrationTest extends TestCase
{
    private function loadMigration(): Migration
    {
        $files = glob(__DIR__.'/../../../database/migrations/2026_06_08_000001_create_external_account_connections_table.php');
        $this->assertNotEmpty($files, 'Migration file should exist on disk.');

        return require $files[0];
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('external_account_connections');
        parent::tearDown();
    }

    public function test_up_creates_table_with_expected_columns(): void
    {
        $this->loadMigration()->up();

        $this->assertTrue(Schema::hasTable('external_account_connections'));

        foreach ([
            'id',
            'user_id',
            'provider',
            'external_user_id',
            'credentials',
            'scopes',
            'token_expires_at',
            'status',
            'last_error',
            'created_at',
            'updated_at',
            'deleted_at',
        ] as $column) {
            $this->assertTrue(
                Schema::hasColumn('external_account_connections', $column),
                "Expected column `{$column}` after up()."
            );
        }
    }

    public function test_up_enforces_unique_user_provider_pair(): void
    {
        $this->loadMigration()->up();

        DB::table('external_account_connections')->insert([
            'user_id' => 1,
            'provider' => 'discord',
            'status' => 'connected',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Same (user_id, provider) — should collide.
        $this->expectException(QueryException::class);
        DB::table('external_account_connections')->insert([
            'user_id' => 1,
            'provider' => 'discord',
            'status' => 'connected',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_up_allows_same_provider_for_different_users(): void
    {
        $this->loadMigration()->up();

        DB::table('external_account_connections')->insert([
            'user_id' => 1,
            'provider' => 'discord',
            'status' => 'connected',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('external_account_connections')->insert([
            'user_id' => 2,
            'provider' => 'discord',
            'status' => 'connected',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertSame(2, DB::table('external_account_connections')->count());
    }

    public function test_provider_token_expires_index_present(): void
    {
        $this->loadMigration()->up();

        // sqlite_master holds index metadata for sqlite; using it keeps the
        // test platform-self-contained without depending on doctrine/dbal.
        $indexes = collect(DB::select(
            "select name from sqlite_master where type = 'index' and tbl_name = 'external_account_connections'"
        ))->pluck('name')->all();

        $this->assertContains(
            'external_account_connections_provider_expires_index',
            $indexes,
            'Migration must create the provider+token_expires_at index used by the refresh-scheduler query.'
        );
    }

    public function test_down_drops_the_table(): void
    {
        $migration = $this->loadMigration();
        $migration->up();
        $this->assertTrue(Schema::hasTable('external_account_connections'));

        $migration->down();
        $this->assertFalse(Schema::hasTable('external_account_connections'));
    }

    public function test_up_is_idempotent_when_table_already_exists(): void
    {
        $migration = $this->loadMigration();
        $migration->up();

        // Second up() must be a no-op — the migration guards on
        // Schema::hasTable so a partial / retried migrate cannot throw.
        $migration->up();

        $this->assertTrue(Schema::hasTable('external_account_connections'));
    }

    public function test_full_up_then_down_round_trips_cleanly(): void
    {
        $migration = $this->loadMigration();
        $migration->up();

        DB::table('external_account_connections')->insert([
            'user_id' => 7,
            'provider' => 'github',
            'external_user_id' => '12345',
            'credentials' => 'encrypted-blob',
            'scopes' => json_encode(['read:user']),
            'token_expires_at' => now()->addHour(),
            'status' => 'connected',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $migration->down();

        $this->assertFalse(Schema::hasTable('external_account_connections'));
    }

    public function test_external_user_id_can_hold_long_subjects(): void
    {
        // OAuth `sub` claims can be 191+ chars (e.g. Google IDs hashed by
        // some IdPs). We declared the column as length 191 deliberately to
        // pair with InnoDB utf8mb4 index limits on consumer setups.
        $this->loadMigration()->up();

        $external = str_repeat('a', 191);

        DB::table('external_account_connections')->insert([
            'user_id' => 9,
            'provider' => 'oauth',
            'external_user_id' => $external,
            'status' => 'connected',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $row = DB::table('external_account_connections')->where('user_id', 9)->first();
        $this->assertSame($external, $row->external_user_id);
    }
}
