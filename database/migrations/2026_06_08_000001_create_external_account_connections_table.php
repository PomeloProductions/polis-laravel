<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Polis\Models\User\ExternalAccountConnection;

/**
 * Create the `external_account_connections` table that backs
 * {@see ExternalAccountConnection}.
 *
 * Schema rationale:
 *
 *  - One row per (user, provider) pair: a user can connect to many
 *    third-party providers but only once per provider, enforced by the
 *    composite unique key. Re-linking the same provider overwrites the
 *    existing row rather than inserting a duplicate.
 *
 *  - `credentials` is a TEXT column holding a Laravel-encrypted JSON blob
 *    (Crypt::encryptString) so tokens never appear in plaintext. The
 *    model's `credentials => encrypted:array` cast handles round-tripping.
 *
 *  - `token_expires_at` is denormalised out of the blob so refresh-token
 *    scheduling can query an indexed column instead of decrypting every
 *    row. Indexed jointly with `provider` for the
 *    findExpiringByProvider() lookup path.
 *
 *  - `scopes` is a separate JSON-encoded column rather than living in the
 *    encrypted blob: scopes are NOT secrets (they're frequently surfaced
 *    in UI) and storing them in cleartext means access-control queries
 *    can filter on them without decryption.
 *
 *  - No explicit FK on user_id: the `users` table is consumer-app-owned
 *    and may not exist at migrate time on every consumer; the Eloquent
 *    relationship handles referential integrity at the ORM layer
 *    (matching the polis-laravel convention used for `organizations`
 *    in the email-template migration).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('external_account_connections')) {
            return;
        }

        Schema::create('external_account_connections', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('provider', 64);
            $table->string('external_user_id', 191)->nullable();
            // TEXT (not VARCHAR) because Laravel's Crypt::encryptString
            // payload exceeds 255 bytes once the JSON is non-trivial.
            $table->text('credentials')->nullable();
            $table->json('scopes')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->string('status', 16)->default('disconnected');
            $table->text('last_error')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Lookup path 1: "does user X have a connection for provider Y?"
            $table->unique(['user_id', 'provider'], 'external_account_connections_user_provider_unique');

            // Lookup path 2: "which connections for provider Y are expiring
            // before timestamp T?" (refresh scheduling job).
            $table->index(['provider', 'token_expires_at'], 'external_account_connections_provider_expires_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_account_connections');
    }
};
