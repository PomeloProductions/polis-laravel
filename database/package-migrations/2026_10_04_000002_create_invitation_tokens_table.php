<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Polis\Models\User\InvitationToken;

/**
 * Create the `invitation_tokens` table that backs {@see InvitationToken}.
 *
 * Package-owned, auto-loaded migration
 * ------------------------------------
 * This file lives in database/package-migrations/, which the base service
 * provider registers via loadMigrationsFrom() so it runs on every consumer's
 * `php artisan migrate` WITHOUT the consumer copying anything. It holds ONLY
 * genuinely package-owned tables that consumers are not expected to hand-write
 * (unlike database/migrations/, whose table-ALTER migrations touch
 * consumer-owned schema and are therefore intentionally NOT auto-loaded).
 *
 * Why this table ships from the package
 * -------------------------------------
 * polis-laravel provides the whole invitation surface — the InvitationToken
 * model, InvitationTokenRepository (generateUniqueToken/findByToken) and the
 * OrganizationManagerController store() path that mints a token when inviting a
 * brand-new email — but historically shipped no migration for the backing
 * table. Each consumer hand-added it (PolisOS, Card-Collecting) or forgot to
 * (client-driver, HighScoresCenter), and the missing table 500'd the
 * invite-a-new-email flow fleet-wide. Shipping it here fixes it once.
 *
 * Idempotency / conflict safety
 * -----------------------------
 * Guarded with Schema::hasTable() exactly like the sibling
 * create_external_account_connections_table package migration. Consumers that
 * already create `invitation_tokens` in their own migration history (PolisOS,
 * Card-Collecting via their earlier 2025_11_24 migration) run that first and
 * this later-timestamped package migration no-ops, so auto-loading it cannot
 * double-create the table. Consumers missing the table (client-driver,
 * HighScoresCenter) get it created here.
 *
 * Schema rationale (mirrors the InvitationToken model's properties/casts):
 *  - `token` varchar(40) UNIQUE: the generated invitation token looked up by
 *    InvitationTokenRepository::findByToken().
 *  - `role_id` unsignedInteger nullable: the org role granted on acceptance.
 *    No FK constraint — `roles` is consumer-app-owned and may not exist at
 *    migrate time on every consumer (matching the polis-laravel convention of
 *    enforcing referential integrity at the ORM layer, as the
 *    external_account_connections migration does for user_id).
 *  - `used_at` nullable timestamp: set when the invitation is accepted;
 *    InvitationToken::isUsed() checks it.
 *  - softDeletes + timestamps: BaseModelAbstract uses SoftDeletes and the
 *    model documents `deleted_at` / `created_at` / `updated_at`.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('invitation_tokens')) {
            return;
        }

        Schema::create('invitation_tokens', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('token', 40)->unique();
            $table->unsignedInteger('role_id')->nullable();
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invitation_tokens');
    }
};
