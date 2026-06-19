<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Test-only owner table for HasExternalSources trait tests.
 *
 * The production `sources` table is created by
 * database/migrations/2026_06_19_000001_create_sources_table.php and is
 * loaded directly inside the trait test class. This fixture migration
 * only creates the owner side — a small Eloquent-backed table whose
 * model uses the trait and acts as the polymorphic target for sources
 * rows during the assertions.
 *
 * The table is intentionally minimal (just id + name + timestamps + soft
 * deletes) because the trait makes no assumptions about its owner model
 * beyond "extends BaseModelAbstract and has a primary key".
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('external_sources_owners')) {
            return;
        }

        Schema::create('external_sources_owners', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_sources_owners');
    }
};
