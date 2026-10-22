<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Polis\Models\User\TwoFactorSecret;

/**
 * Create the `two_factor_secrets` table that backs {@see TwoFactorSecret}.
 *
 * Package-owned, auto-loaded migration
 * ------------------------------------
 * Lives in database/package-migrations/, which the base service provider
 * registers via loadMigrationsFrom() so it runs on every consumer's
 * `php artisan migrate` WITHOUT the consumer copying anything. It holds a
 * genuinely package-owned table (the TOTP second-factor secret) that no
 * consumer is expected to hand-write — exactly like invitation_tokens and
 * external_account_connections.
 *
 * Schema rationale
 * ----------------
 *  - One row per user (enforced by the UNIQUE index on `user_id`): a user has
 *    at most one active authenticator-app secret. Re-enrolling overwrites the
 *    pending secret rather than inserting a duplicate.
 *
 *  - `secret` is a TEXT column holding a Laravel-encrypted string
 *    (`encrypted` cast). The raw base32 TOTP seed is the shared secret a MITM
 *    with DB access could use to mint valid codes forever, so it is never
 *    stored in plaintext. TEXT (not VARCHAR) because the encrypted payload
 *    exceeds 255 bytes.
 *
 *  - `recovery_codes` is a TEXT column holding a Laravel-encrypted JSON array
 *    of *hashed* single-use recovery codes (`encrypted:array`). Each code is
 *    individually bcrypt-hashed before storage — the encryption is
 *    defence-in-depth on top of the hashing, matching the
 *    external_account_connections precedent for secret-at-rest.
 *
 *  - `confirmed_at` nullable timestamp: enrollment is a two-step handshake.
 *    enroll() writes the secret with confirmed_at = null (pending); confirm()
 *    sets it once the user proves possession with a valid code. A null
 *    confirmed_at means 2FA is NOT yet active for the user, so login must not
 *    challenge on it.
 *
 *  - No FK on user_id: the `users` table is consumer-app-owned and may not
 *    exist at migrate time on every consumer. Referential integrity is handled
 *    at the ORM layer (the model's belongsTo + the repository), matching the
 *    polis-laravel convention used for external_account_connections and
 *    invitation_tokens.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('two_factor_secrets')) {
            return;
        }

        Schema::create('two_factor_secrets', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            // TEXT because Laravel's Crypt payload exceeds 255 bytes.
            $table->text('secret');
            $table->text('recovery_codes')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // At most one secret per user.
            $table->unique('user_id', 'two_factor_secrets_user_id_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('two_factor_secrets');
    }
};
