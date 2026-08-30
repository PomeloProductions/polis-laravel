<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create the standard Laravel `failed_jobs` table.
 *
 * WHY this lives in the package: the Pomelo platform controller now defaults
 * every laravel tenant's cache + session + queue to the shared redis cluster so
 * >=2 replicas are consistent (see client-driver ServiceHelmValues). With a
 * redis QUEUE_CONNECTION, jobs run out of redis — but Laravel STILL persists
 * FAILED queue jobs to the DATABASE `failed_jobs` table (the framework's
 * `database-uuids` failed-job provider). Without this table, the first failed
 * job throws "Base table or view not found: failed_jobs" instead of being
 * recorded, and the failure is lost.
 *
 * NOTE: no cache/sessions tables are added — those live in redis now, not the
 * DB. This is the ONLY DB table the redis migration requires.
 *
 * Constraints honored:
 *  - Idempotent `Schema::hasTable` guard, matching the package's other
 *    migrations (some tenant DBs may already carry an app-shipped failed_jobs).
 *  - `$table->id()` gives a BIGINT auto-increment PRIMARY KEY, required by the
 *    managed MySQL's `sql_require_primary_key` (a table with no PK would be
 *    rejected at CREATE time).
 *
 * Schema matches Laravel's canonical failed_jobs (uuid unique + connection /
 * queue / payload / exception + failed_at), so the framework's failed-job
 * provider works without customization.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('failed_jobs')) {
            return;
        }

        Schema::create('failed_jobs', function (Blueprint $table): void {
            // BIGINT auto-increment PRIMARY KEY — satisfies the managed MySQL's
            // sql_require_primary_key.
            $table->id();
            $table->string('uuid')->unique();
            $table->text('connection');
            $table->text('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('failed_jobs');
    }
};
