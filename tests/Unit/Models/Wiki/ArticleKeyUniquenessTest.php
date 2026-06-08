<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Models\Wiki;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Polis\Tests\TestCase;

/**
 * Class ArticleKeyUniquenessTest
 *
 * Schema-level test for the unique constraint introduced by
 * 2026_06_05_000001_add_unique_constraint_on_articles_key_organization_id.php.
 *
 * The articles table is normally created by the consumer application; the
 * polis-laravel package itself only ships additive migrations. To exercise
 * this constraint in isolation we hand-build a minimal `articles` schema
 * (id + title + key + organization_id) on the in-memory sqlite connection
 * configured by phpunit.xml, then run the new migration against it.
 *
 * The semantics we verify (mirror the doc-block in the migration):
 *   1. A row with key='welcome' and organization_id=NULL inserts cleanly.
 *   2. A SECOND row with the same key+org_id=NULL still inserts on sqlite
 *      (NULL != NULL in unique indexes per SQL standard). This is a
 *      KNOWN GAP on sqlite/mysql; application code is the second line of
 *      defense, and PostgreSQL gets the COALESCE-based partial unique
 *      (covered by the doc-block, not asserted here because Testbench
 *      uses sqlite).
 *   3. Two rows that share a non-null key AND a non-null organization_id
 *      DO collide.
 *   4. Two rows with the same key but different non-null
 *      organization_ids both insert (different tenants, different rows).
 *   5. The constraint is by-name droppable in down().
 */
final class ArticleKeyUniquenessTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Minimal articles schema sufficient for the unique-constraint test.
        // The consumer app's real `articles` table has many more columns;
        // we only need the ones referenced by the migration.
        Schema::create('articles', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('title')->nullable();
            $table->string('key', 100)->nullable();
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->timestamps();
        });

        // Run the unique-constraint migration against the schema above.
        $this->loadUniqueConstraintMigration()->up();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('articles');
        parent::tearDown();
    }

    public function test_inserts_succeed_for_distinct_key_org_pairs(): void
    {
        DB::table('articles')->insert([
            'title' => 'Welcome (email)', 'key' => 'welcome', 'organization_id' => null,
        ]);

        // Same key with a NON-null org_id is a separate row (tenant override).
        DB::table('articles')->insert([
            'title' => 'Welcome override (org 42)', 'key' => 'welcome', 'organization_id' => 42,
        ]);

        // Different key, different tenant.
        DB::table('articles')->insert([
            'title' => 'Renewal (org 42)', 'key' => 'renewal_reminder', 'organization_id' => 42,
        ]);

        $this->assertSame(3, DB::table('articles')->count());
    }

    public function test_distinct_org_ids_with_same_key_do_not_collide(): void
    {
        DB::table('articles')->insert([
            'title' => 'Welcome (org 1)', 'key' => 'welcome', 'organization_id' => 1,
        ]);
        DB::table('articles')->insert([
            'title' => 'Welcome (org 2)', 'key' => 'welcome', 'organization_id' => 2,
        ]);

        $this->assertSame(2, DB::table('articles')->where('key', 'welcome')->count());
    }

    public function test_duplicate_key_with_same_non_null_org_id_throws(): void
    {
        DB::table('articles')->insert([
            'title' => 'Welcome (org 7)', 'key' => 'welcome', 'organization_id' => 7,
        ]);

        // The email-template row already exists for (welcome, 7). A push
        // template trying to claim the same (key, org) pair must collide.
        $this->expectException(QueryException::class);

        DB::table('articles')->insert([
            'title' => 'Welcome push (org 7)', 'key' => 'welcome', 'organization_id' => 7,
        ]);
    }

    public function test_null_key_rows_do_not_participate_in_constraint(): void
    {
        // Two non-template articles (key=NULL) should never collide; they
        // are wiki entries / regular articles and predate this constraint.
        DB::table('articles')->insert([
            'title' => 'Some wiki entry A', 'key' => null, 'organization_id' => null,
        ]);
        DB::table('articles')->insert([
            'title' => 'Some wiki entry B', 'key' => null, 'organization_id' => null,
        ]);
        DB::table('articles')->insert([
            'title' => 'Some wiki entry C', 'key' => null, 'organization_id' => 7,
        ]);
        DB::table('articles')->insert([
            'title' => 'Some wiki entry D', 'key' => null, 'organization_id' => 7,
        ]);

        $this->assertSame(4, DB::table('articles')->whereNull('key')->count());
    }

    public function test_down_drops_the_constraint(): void
    {
        $migration = $this->loadUniqueConstraintMigration();

        // After up() (run in setUp), the constraint blocks duplicates.
        DB::table('articles')->insert([
            'title' => 'Welcome', 'key' => 'welcome', 'organization_id' => 5,
        ]);
        $threw = false;
        try {
            DB::table('articles')->insert([
                'title' => 'Welcome (dup)', 'key' => 'welcome', 'organization_id' => 5,
            ]);
        } catch (QueryException $e) {
            $threw = true;
        }
        $this->assertTrue($threw, 'pre-down: duplicate should have collided');

        // After down(), the constraint is gone; a duplicate insert succeeds.
        $migration->down();
        DB::table('articles')->insert([
            'title' => 'Welcome (dup, post-down)', 'key' => 'welcome', 'organization_id' => 5,
        ]);
        $this->assertSame(2, DB::table('articles')->where('key', 'welcome')->where('organization_id', 5)->count());
    }

    private function loadUniqueConstraintMigration(): Migration
    {
        return require __DIR__.'/../../../../database/migrations/2026_06_05_000001_add_unique_constraint_on_articles_key_organization_id.php';
    }
}
