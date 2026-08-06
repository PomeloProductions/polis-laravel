<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Org-scopes the `articles` table so an organization's "contracts" (Articles)
 * can be listed efficiently on the dashboard Organization-detail page.
 *
 * Background
 * ----------
 * The `articles.organization_id` column already exists — it was added by
 * 2026_05_30_000001_add_key_and_organization_id_to_articles_table for the
 * EmailTemplate/PushTemplate multi-tenant override feature, but only inside a
 * composite `(key, organization_id)` index. That composite index is NOT usable
 * for a bare `where organization_id = ?` lookup (leading column is `key`), so
 * listing every Article owned by an organization would table-scan.
 *
 * This migration adds a standalone `articles_organization_id_index` on
 * `organization_id` alone, which is what the org-scoped
 * `GET /organizations/{org}/articles` path filters on
 * (see ArticleRepository / OrganizationArticleControllerAbstract).
 *
 * It is fully additive and non-breaking:
 *   - guards on Schema::hasColumn so it also works for a consumer that has not
 *     yet run the 2026_05_30 migration (it will add the nullable column),
 *   - guards on the index name so a retried / partial migrate is a no-op,
 *   - null organization_id continues to mean "platform-wide" (a global wiki
 *     Article / global email template) and is untouched.
 *
 * No foreign-key constraint is declared: the `organizations` table is owned by
 * the consuming app and may not exist at migrate time on every consumer, matching
 * the decision already made in the 2026_05_30 migration. The Article->organization()
 * relation enforces the association at the ORM layer.
 */
return new class extends Migration
{
    private const INDEX_NAME = 'articles_organization_id_index';

    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table): void {
            if (! Schema::hasColumn('articles', 'organization_id')) {
                $table->unsignedBigInteger('organization_id')->nullable();
            }
        });

        if (! $this->indexExists(self::INDEX_NAME)) {
            Schema::table('articles', function (Blueprint $table): void {
                $table->index('organization_id', self::INDEX_NAME);
            });
        }
    }

    public function down(): void
    {
        if ($this->indexExists(self::INDEX_NAME)) {
            Schema::table('articles', function (Blueprint $table): void {
                $table->dropIndex(self::INDEX_NAME);
            });
        }
    }

    /**
     * Driver-agnostic check for whether an index already exists on `articles`.
     *
     * Uses the Schema builder's introspection (available on Laravel 11+) so we
     * don't depend on doctrine/dbal, which is not installed in this package.
     */
    private function indexExists(string $name): bool
    {
        $target = strtolower($name);

        foreach (Schema::getIndexes('articles') as $index) {
            if (strtolower((string) ($index['name'] ?? '')) === $target) {
                return true;
            }
        }

        return false;
    }
};
