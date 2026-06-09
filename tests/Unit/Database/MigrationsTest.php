<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Database;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Polis\Tests\TestCase;

/**
 * Integrity suite for every migration shipped in database/migrations/.
 *
 * polis-laravel only ships additive migrations against tables that the
 * consumer app owns (notably `articles`). In Testbench's sqlite-in-memory
 * harness we hand-build a minimal version of those parent tables, then:
 *
 *   1. Discover every migration file in chronological order.
 *   2. Run each up().
 *   3. Run each down() in reverse order.
 *   4. Assert no errors, and (for the unique-constraint migration) that
 *      the constraint exists after up() and is gone after down().
 *
 * This guards against two classes of regression:
 *   - "someone added a migration that doesn't work on sqlite"
 *   - "a down() is missing or doesn't fully roll back its up()"
 *
 * The existing ArticleKeyUniquenessTest covers the semantic behaviour of
 * the unique constraint in detail; this test is the schema-integrity
 * counterpart and exercises every migration in the directory, not just
 * one.
 */
final class MigrationsTest extends TestCase
{
    /**
     * @return Migration[]
     */
    private function discoverMigrations(): array
    {
        $files = $this->discoverMigrationFiles();

        return array_map(
            fn (string $path) => require $path,
            $files,
        );
    }

    /**
     * Migration filenames in chronological order. Returned as a separate
     * helper so individual tests can pull a specific migration out of the
     * set by name (e.g. the unique-constraint migration, which is
     * referenced by name in the down() / re-up() tests below).
     *
     * @return string[]
     */
    private function discoverMigrationFiles(): array
    {
        $files = glob(__DIR__.'/../../../database/migrations/*.php');
        sort($files); // chronological by filename prefix.

        return $files;
    }

    /**
     * Locate the migration file that adds the unique constraint on
     * (key, organization_id) and require it. The unique-constraint-specific
     * tests below name this migration explicitly so that adding new
     * unrelated migrations to database/migrations/ (which then becomes the
     * lexicographically-last file) does not silently break the test by
     * pointing end($migrations) at the wrong row.
     */
    private function loadUniqueConstraintMigration(): Migration
    {
        $needle = 'add_unique_constraint_on_articles_key_organization_id';
        foreach ($this->discoverMigrationFiles() as $file) {
            if (str_contains($file, $needle)) {
                return require $file;
            }
        }
        $this->fail("Could not locate the {$needle} migration.");
    }

    private function buildArticlesBaseSchema(): void
    {
        // Minimal `articles` table covering only the columns required by
        // the migrations under test. The consumer app owns the real
        // articles table; here we stub the parent so the additive
        // migrations have something to alter.
        Schema::create('articles', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('title')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('articles');
        // Any tables created by the discovered migrations themselves need
        // to be dropped here too, since this test discovers EVERY migration
        // in database/migrations/ and some create their own tables.
        Schema::dropIfExists('external_account_connections');
        parent::tearDown();
    }

    public function test_all_migrations_run_up_without_errors(): void
    {
        $this->buildArticlesBaseSchema();
        $migrations = $this->discoverMigrations();
        $this->assertNotEmpty($migrations, 'No migrations discovered to exercise.');

        foreach ($migrations as $migration) {
            $migration->up();
        }

        // sanity: the articles table still exists and has gained columns
        // beyond the bare {id, title, timestamps} baseline.
        $this->assertTrue(Schema::hasTable('articles'));
        $this->assertTrue(
            Schema::hasColumn('articles', 'key'),
            'Up migrations should have added the `key` column to articles.'
        );
        $this->assertTrue(
            Schema::hasColumn('articles', 'organization_id'),
            'Up migrations should have added the `organization_id` column to articles.'
        );
    }

    public function test_all_migrations_run_down_in_reverse_order_without_errors(): void
    {
        $this->buildArticlesBaseSchema();
        $migrations = $this->discoverMigrations();

        foreach ($migrations as $migration) {
            $migration->up();
        }

        // Reverse order on down — newest migration first, matching how
        // Laravel's migrator rolls back batches.
        foreach (array_reverse($migrations) as $migration) {
            $migration->down();
        }

        // After all down()s the additive columns must be gone.
        $this->assertFalse(
            Schema::hasColumn('articles', 'key'),
            'down() should have dropped the `key` column.'
        );
        $this->assertFalse(
            Schema::hasColumn('articles', 'organization_id'),
            'down() should have dropped the `organization_id` column.'
        );
    }

    public function test_unique_constraint_migration_enforces_uniqueness_after_up(): void
    {
        $this->buildArticlesBaseSchema();

        foreach ($this->discoverMigrations() as $migration) {
            $migration->up();
        }

        // First insert succeeds.
        DB::table('articles')->insert([
            'title' => 'Welcome',
            'key' => 'welcome',
            'organization_id' => 11,
        ]);

        // Duplicate (key, organization_id) should now collide.
        $this->expectException(QueryException::class);
        DB::table('articles')->insert([
            'title' => 'Welcome (dup)',
            'key' => 'welcome',
            'organization_id' => 11,
        ]);
    }

    public function test_unique_constraint_migration_drops_constraint_on_down(): void
    {
        $this->buildArticlesBaseSchema();
        $migrations = $this->discoverMigrations();

        foreach ($migrations as $migration) {
            $migration->up();
        }

        DB::table('articles')->insert([
            'title' => 'Welcome', 'key' => 'welcome', 'organization_id' => 22,
        ]);

        // Roll back just the unique-constraint migration. We look it up by
        // filename rather than by array position so additional migrations
        // added later (which would become the lexicographically-last
        // entry) do not silently shift this test onto the wrong row.
        $this->loadUniqueConstraintMigration()->down();

        // After down() the duplicate insert must NOT throw.
        DB::table('articles')->insert([
            'title' => 'Welcome (dup, post-down)',
            'key' => 'welcome',
            'organization_id' => 22,
        ]);

        $this->assertSame(
            2,
            DB::table('articles')->where('key', 'welcome')->where('organization_id', 22)->count(),
            'After down() of the unique-constraint migration, duplicate rows must coexist.'
        );

        // Note: we do NOT re-run up() here. The articles table already
        // contains the duplicate (welcome, 22) pair, so recreating the
        // unique index would (correctly) fail. tearDown drops the table
        // so leaving the constraint absent is fine.
    }

    /**
     * Documents a known partial-idempotency gap in the 2026_05_30 migration.
     *
     * The column adds are guarded with Schema::hasColumn() checks (good),
     * but the trailing `$table->index([...], 'articles_key_organization_id_index')`
     * is UNCONDITIONAL. On a second up() against the same schema it raises
     * "index already exists".
     *
     * Laravel's migrator never reruns the same migration row in normal
     * deployments, so this isn't a production hazard today — but it does
     * make the migration painful to debug locally and prevents safe partial
     * re-runs. Recommended fix:
     *
     *     $existing = DB::getDoctrineSchemaManager()->listTableIndexes('articles');
     *     if (! isset($existing['articles_key_organization_id_index'])) {
     *         $table->index(['key', 'organization_id'], 'articles_key_organization_id_index');
     *     }
     *
     * This test asserts the CURRENT behaviour (second up() throws) so that
     * once the migration is hardened, the assertion can be flipped to
     * assertDoesNotThrow and serve as a regression test.
     */
    public function test_first_migration_index_creation_is_not_idempotent_today(): void
    {
        $this->buildArticlesBaseSchema();
        $migrations = $this->discoverMigrations();
        $first = $migrations[0];

        $first->up();

        // Column-level guards work correctly — a re-run won't throw on
        // hasColumn-protected column adds. The unconditional index() call
        // is the offender.
        $this->expectException(QueryException::class);
        $this->expectExceptionMessageMatches('/index .*articles_key_organization_id_index.* already exists/');

        $first->up();
    }
}
