<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Test-only minimal `articles` table for the EmailTemplate / PushTemplate
 * repository tests.
 *
 * polis-laravel does NOT ship its own articles migration (the consumer
 * application owns that table). For tests of
 * Polis\Repositories\Messaging\EmailTemplateRepository::findByKey and the
 * matching PushTemplate path, we need an `articles` table at minimum and
 * the additive `key` + `organization_id` columns from
 * database/migrations/2026_05_30_000001_add_key_and_organization_id_to_articles_table.php.
 *
 * Columns kept deliberately small: `id`, `title`, `key`,
 * `organization_id`, `created_by_id` (to satisfy fillable on EmailTemplate
 * / PushTemplate), `created_at`, `updated_at`, `deleted_at`. This is the
 * exact subset of the consumer-app articles table that the template
 * repository's lookup methods touch.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('articles')) {
            return;
        }

        Schema::create('articles', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->string('title', 191)->nullable();
            $table->string('key', 100)->nullable();
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->string('url')->nullable();
            $table->string('authors')->nullable();
            $table->tinyInteger('has_full_modification_history')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['key', 'organization_id'], 'articles_key_organization_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
