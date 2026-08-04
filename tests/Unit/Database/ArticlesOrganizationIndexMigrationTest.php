<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Database;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Polis\Tests\TestCase;

/**
 * Schema-level coverage for
 * 2026_08_04_000001_add_organization_index_to_articles_table.
 *
 * The migration is additive over the consumer-owned `articles` table. Because
 * the table is normally owned by the consuming app, this test stands up a
 * minimal `articles` table itself (mirroring the two states the migration must
 * tolerate) and then drives up()/down() directly against in-memory sqlite.
 *
 * Assertions:
 *   1. up() adds the standalone organization_id index.
 *   2. up() creates the organization_id column when a consumer hasn't already
 *      (guarded add), so the migration is safe on its own.
 *   3. up() is idempotent — re-running does not throw on the existing index.
 *   4. down() drops the index.
 */
final class ArticlesOrganizationIndexMigrationTest extends TestCase
{
    private function loadMigration(): Migration
    {
        $files = glob(__DIR__.'/../../../database/migrations/2026_08_04_000001_add_organization_index_to_articles_table.php');
        $this->assertNotEmpty($files, 'Migration file should exist on disk.');

        return require $files[0];
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('articles');
        parent::tearDown();
    }

    private function indexNames(): array
    {
        return array_map(
            static fn (array $i): string => strtolower((string) ($i['name'] ?? '')),
            Schema::getIndexes('articles'),
        );
    }

    public function test_up_adds_index_when_column_already_exists(): void
    {
        Schema::create('articles', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->unsignedBigInteger('organization_id')->nullable();
        });

        $this->loadMigration()->up();

        $this->assertContains('articles_organization_id_index', $this->indexNames());
    }

    public function test_up_creates_column_when_absent(): void
    {
        // Simulate a consumer that has NOT run the 2026_05_30 migration.
        Schema::create('articles', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
        });
        $this->assertFalse(Schema::hasColumn('articles', 'organization_id'));

        $this->loadMigration()->up();

        $this->assertTrue(Schema::hasColumn('articles', 'organization_id'));
        $this->assertContains('articles_organization_id_index', $this->indexNames());
    }

    public function test_up_is_idempotent(): void
    {
        Schema::create('articles', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->unsignedBigInteger('organization_id')->nullable();
        });

        $migration = $this->loadMigration();
        $migration->up();
        // Second up() must not throw on the already-present index.
        $migration->up();

        $this->assertContains('articles_organization_id_index', $this->indexNames());
    }

    public function test_down_drops_the_index(): void
    {
        Schema::create('articles', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->unsignedBigInteger('organization_id')->nullable();
        });

        $migration = $this->loadMigration();
        $migration->up();
        $this->assertContains('articles_organization_id_index', $this->indexNames());

        $migration->down();
        $this->assertNotContains('articles_organization_id_index', $this->indexNames());
    }
}
