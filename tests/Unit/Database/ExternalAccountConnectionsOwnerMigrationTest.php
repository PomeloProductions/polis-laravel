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
 * 2026_08_06_000004_add_owner_to_external_account_connections_table.
 */
final class ExternalAccountConnectionsOwnerMigrationTest extends TestCase
{
    private const INDEX_NAME = 'external_account_connections_owner_index';

    private function loadMigration(): Migration
    {
        $files = glob(__DIR__.'/../../../database/migrations/2026_08_06_000004_add_owner_to_external_account_connections_table.php');
        $this->assertNotEmpty($files, 'Migration file should exist on disk.');

        return require $files[0];
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('external_account_connections');
        parent::tearDown();
    }

    private function indexNames(): array
    {
        return array_map(
            static fn (array $i): string => strtolower((string) ($i['name'] ?? '')),
            Schema::getIndexes('external_account_connections'),
        );
    }

    private function createLegacyTable(): void
    {
        Schema::create('external_account_connections', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('provider', 64);
            $table->unique(['user_id', 'provider'], 'external_account_connections_user_provider_unique');
        });
    }

    public function test_up_adds_owner_columns_and_index_and_retains_user_id_and_unique(): void
    {
        $this->createLegacyTable();

        $this->loadMigration()->up();

        $this->assertTrue(Schema::hasColumn('external_account_connections', 'owner_id'));
        $this->assertTrue(Schema::hasColumn('external_account_connections', 'owner_type'));
        $this->assertTrue(Schema::hasColumn('external_account_connections', 'user_id'));
        $this->assertContains(self::INDEX_NAME, $this->indexNames());
        // The pre-existing composite unique index MUST survive.
        $this->assertContains('external_account_connections_user_provider_unique', $this->indexNames());
    }

    public function test_up_backfills_owner_from_user_id(): void
    {
        $this->createLegacyTable();
        DB::table('external_account_connections')->insert(['id' => 1, 'user_id' => 5, 'provider' => 'discord']);

        $this->loadMigration()->up();

        $row = DB::table('external_account_connections')->where('id', 1)->first();
        $this->assertSame(5, (int) $row->owner_id);
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
        $this->assertFalse(Schema::hasColumn('external_account_connections', 'owner_id'));
        $this->assertTrue(Schema::hasColumn('external_account_connections', 'user_id'));
    }
}
