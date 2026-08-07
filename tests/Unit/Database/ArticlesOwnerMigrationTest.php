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
 * 2026_08_06_000001_add_owner_to_articles_table.
 *
 * The migration converts `articles` from the single-owner organization_id FK to
 * the polymorphic owner_id/owner_type shape, additively and non-destructively:
 * it adds the nullable columns, backfills them from organization_id, retains
 * organization_id, and adds a composite owner index. This test drives up()/down()
 * directly against in-memory sqlite.
 */
final class ArticlesOwnerMigrationTest extends TestCase
{
    private const INDEX_NAME = 'articles_owner_index';

    private function loadMigration(): Migration
    {
        $files = glob(__DIR__.'/../../../database/migrations/2026_08_06_000001_add_owner_to_articles_table.php');
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

    private function createLegacyArticlesTable(): void
    {
        Schema::create('articles', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->unsignedBigInteger('organization_id')->nullable();
        });
    }

    public function test_up_adds_nullable_owner_columns_and_index(): void
    {
        $this->createLegacyArticlesTable();

        $this->loadMigration()->up();

        $this->assertTrue(Schema::hasColumn('articles', 'owner_id'));
        $this->assertTrue(Schema::hasColumn('articles', 'owner_type'));
        // organization_id is RETAINED (non-destructive).
        $this->assertTrue(Schema::hasColumn('articles', 'organization_id'));
        $this->assertContains(self::INDEX_NAME, $this->indexNames());
    }

    public function test_up_backfills_owner_from_organization_id(): void
    {
        $this->createLegacyArticlesTable();

        DB::table('articles')->insert([
            ['id' => 1, 'title' => 'Org contract', 'organization_id' => 55],
            ['id' => 2, 'title' => 'Global wiki article', 'organization_id' => null],
        ]);

        $this->loadMigration()->up();

        $org = DB::table('articles')->where('id', 1)->first();
        $this->assertSame(55, (int) $org->owner_id);
        $this->assertSame('organization', $org->owner_type);
        // organization_id preserved.
        $this->assertSame(55, (int) $org->organization_id);

        $global = DB::table('articles')->where('id', 2)->first();
        $this->assertNull($global->owner_id);
        $this->assertNull($global->owner_type);
    }

    public function test_up_does_not_clobber_existing_owner(): void
    {
        Schema::create('articles', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->unsignedBigInteger('owner_id')->nullable();
            $table->string('owner_type')->nullable();
        });

        // A row already owned by a user must NOT be overwritten by org backfill.
        DB::table('articles')->insert([
            'id' => 1,
            'title' => 'User-owned',
            'organization_id' => 7,
            'owner_id' => 99,
            'owner_type' => 'user',
        ]);

        $this->loadMigration()->up();

        $row = DB::table('articles')->where('id', 1)->first();
        $this->assertSame(99, (int) $row->owner_id);
        $this->assertSame('user', $row->owner_type);
    }

    public function test_up_creates_columns_when_organization_id_absent(): void
    {
        // A consumer that never ran the 2026_05_30 migration.
        Schema::create('articles', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
        });
        $this->assertFalse(Schema::hasColumn('articles', 'organization_id'));

        $this->loadMigration()->up();

        $this->assertTrue(Schema::hasColumn('articles', 'owner_id'));
        $this->assertTrue(Schema::hasColumn('articles', 'owner_type'));
        $this->assertContains(self::INDEX_NAME, $this->indexNames());
    }

    public function test_up_is_idempotent(): void
    {
        $this->createLegacyArticlesTable();

        $migration = $this->loadMigration();
        $migration->up();
        // Second up() must not throw on the already-present columns/index.
        $migration->up();

        $this->assertContains(self::INDEX_NAME, $this->indexNames());
    }

    public function test_down_drops_owner_columns_and_index(): void
    {
        $this->createLegacyArticlesTable();

        $migration = $this->loadMigration();
        $migration->up();
        $this->assertContains(self::INDEX_NAME, $this->indexNames());

        $migration->down();

        $this->assertNotContains(self::INDEX_NAME, $this->indexNames());
        $this->assertFalse(Schema::hasColumn('articles', 'owner_id'));
        $this->assertFalse(Schema::hasColumn('articles', 'owner_type'));
        // organization_id survives down() — it is the source of truth.
        $this->assertTrue(Schema::hasColumn('articles', 'organization_id'));
    }
}
