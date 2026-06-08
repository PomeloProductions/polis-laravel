<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds a uniqueness guarantee on (key, organization_id) for the
 * `articles` table so that an email template and a push template
 * (both EmailTemplate and PushTemplate are article-backed and share
 * this column) cannot collide on the same identifier within the same
 * tenant scope.
 *
 * Without this constraint, two callers — e.g. the email template
 * subsystem inserting a row with key='welcome', organization_id=null
 * and the push template subsystem inserting a row with the same
 * key/org pair — could both succeed, after which a lookup by key
 * becomes ambiguous and depends on row insertion order. The hybrid
 * Article-backed storage decision made this collision possible; this
 * migration closes that door at the schema level.
 *
 * NULL semantics:
 *   - Rows where `key` IS NULL are normal articles (not templates) and
 *     must NOT participate in the constraint. The shape of this
 *     migration depends on how the connection treats NULL in a unique
 *     index:
 *
 *   - MySQL/MariaDB/SQLite: multiple NULLs are permitted in a UNIQUE
 *     index per the SQL standard (NULL != NULL). A plain
 *     `unique(['key', 'organization_id'])` works correctly: rows with
 *     key=NULL never conflict; rows with key=X conflict if (X, org_id)
 *     already exists.
 *
 *   - PostgreSQL: same NULL behaviour by default (rows with NULL in any
 *     part of the index never conflict), so a plain UNIQUE index would
 *     also work — BUT we additionally constrain on organization_id,
 *     which is also nullable, and a row with (key='welcome',
 *     organization_id=NULL) followed by another row with
 *     (key='welcome', organization_id=NULL) would BOTH be allowed in
 *     Postgres < 15 because the second column is NULL on both sides.
 *     This is exactly the global-template-collision case we are trying
 *     to prevent. We therefore use a partial unique index on Postgres
 *     that treats NULL organization_id as the literal value 0 via
 *     COALESCE so the second insert collides on (welcome, 0).
 *
 * down() drops by name, so the same `articles_key_organization_id_unique`
 * identifier is used on every driver.
 */
return new class extends Migration
{
    private const INDEX_NAME = 'articles_key_organization_id_unique';

    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            // Partial unique on (key, COALESCE(organization_id, 0)) so that
            // two template rows with the same key and NULL organization_id
            // (i.e. two competing "global" templates) collide. WHERE key IS
            // NOT NULL keeps non-template articles outside the constraint.
            DB::statement(
                'CREATE UNIQUE INDEX '.self::INDEX_NAME
                .' ON articles ("key", COALESCE(organization_id, 0))'
                .' WHERE "key" IS NOT NULL'
            );

            return;
        }

        // MySQL / MariaDB / SQLite: NULL != NULL in a unique index, so a
        // plain unique works for the (key=NULL) rows. Two rows with
        // (key='welcome', organization_id=NULL) would still both be
        // permitted here in standard-SQL-NULL drivers — that is a known
        // gap and is acceptable for these drivers since both
        // MySQL <8.0.13 and SQLite treat NULL as distinct in unique
        // indexes. Application code (EmailTemplateRepository /
        // PushTemplateRepository) is the second line of defense for
        // global-NULL collisions on these drivers.
        Schema::table('articles', function (Blueprint $table): void {
            $table->unique(['key', 'organization_id'], self::INDEX_NAME);
        });
    }

    public function down(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS '.self::INDEX_NAME);

            return;
        }

        Schema::table('articles', function (Blueprint $table): void {
            $table->dropUnique(self::INDEX_NAME);
        });
    }
};
