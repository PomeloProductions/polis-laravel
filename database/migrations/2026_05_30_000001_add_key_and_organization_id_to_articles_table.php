<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the runtime-editable email template support columns to the existing
 * `articles` table.
 *
 * Why on `articles`? polis-laravel v0.2 stores email templates as
 * specialized Article rows (see Polis\Models\Messaging\EmailTemplate) so we
 * inherit version history, iteration tracking, and the modification audit
 * pipeline for free. The two additive columns are:
 *
 *   - `key` (nullable string, indexed): stable template identifier such as
 *     `welcome` or `renewal_reminder`. Non-template articles leave this null;
 *     the EmailTemplate model's global scope filters by `whereNotNull('key')`.
 *
 *   - `organization_id` (nullable foreign key): multi-tenant override target.
 *     null means "global default for all tenants"; a value means "override
 *     for this specific organization." See
 *     EmailTemplateRepository::findByKey for the lookup hierarchy.
 *
 * Both columns are nullable so this migration is non-destructive for any
 * existing articles. We use a composite index on (key, organization_id) to
 * make the multi-tenant lookup fast.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table): void {
            if (! Schema::hasColumn('articles', 'key')) {
                $table->string('key', 100)->nullable()->after('title');
            }
            if (! Schema::hasColumn('articles', 'organization_id')) {
                // Not declared as a foreign-key constraint here because the
                // `organizations` table is owned by the consuming app and
                // may not exist at migrate time on every consumer. The
                // EmailTemplate->organization() relation handles it at the
                // ORM layer.
                $table->unsignedBigInteger('organization_id')->nullable()->after('key');
            }
            $table->index(['key', 'organization_id'], 'articles_key_organization_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table): void {
            $table->dropIndex('articles_key_organization_id_index');
            if (Schema::hasColumn('articles', 'organization_id')) {
                $table->dropColumn('organization_id');
            }
            if (Schema::hasColumn('articles', 'key')) {
                $table->dropColumn('key');
            }
        });
    }
};
