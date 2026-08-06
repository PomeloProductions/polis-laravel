<?php

declare(strict_types=1);

namespace Polis\Models\User;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Polis\Models\BaseModelAbstract;
use Polis\Models\Traits\IsOwnedByEntity;

/**
 * Class ExternalAccountConnection
 *
 * Stores a user's OAuth-connected credentials for an external service
 * (Discord, GitHub, Stripe, Patreon, PriceCharting, CardMarket, etc.).
 *
 * Credential storage is intentionally schema-generic: the bulk of provider
 * data lives in `credentials` (a JSON blob encrypted-at-rest via Laravel's
 * `encrypted:array` cast) so this single model can absorb access tokens,
 * refresh tokens, scopes, signing secrets, or anything else a third-party
 * SDK demands without per-provider schema churn. The explicit
 * `token_expires_at` column is denormalised out of the blob so refresh
 * scheduling can be driven by a DB index rather than a full-table scan.
 *
 * Encryption uses Laravel's built-in `encrypted:array` cast which transparently
 * runs Crypt::encryptString / decryptString around the JSON blob using
 * APP_KEY. Tokens never appear in plaintext in the database; reading
 * `$model->credentials` returns the decrypted array, writing reverses it.
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $owner_id
 * @property string|null $owner_type
 * @property string $provider A provider identifier ('discord', 'github', etc.).
 * @property string|null $external_user_id The provider-side user identifier (sub claim, etc.).
 * @property array|null $credentials Decrypted credentials map (access_token, refresh_token, ...).
 * @property array|null $scopes OAuth scopes granted by the user.
 * @property Carbon|null $token_expires_at
 * @property string $status One of self::STATUS_* constants.
 * @property string|null $last_error Last error message recorded against this connection.
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read User $user
 * @property-read Model|\Eloquent|null $owner
 */
class ExternalAccountConnection extends BaseModelAbstract
{
    use IsOwnedByEntity;

    public const STATUS_CONNECTED = 'connected';

    public const STATUS_DISCONNECTED = 'disconnected';

    public const STATUS_ERROR = 'error';

    /**
     * Mass-assignment is opt-in to keep callers from accidentally writing
     * encrypted_credentials in plaintext via array syntax. Use the
     * `credentials` accessor (which routes through the encrypted cast).
     */
    protected $fillable = [
        'user_id',
        'owner_id',
        'owner_type',
        'provider',
        'external_user_id',
        'credentials',
        'scopes',
        'token_expires_at',
        'status',
        'last_error',
    ];

    /**
     * Encrypted-at-rest casts. `encrypted:array` serialises the value to JSON
     * and encrypts the resulting string with the app key. Decrypting on read
     * is automatic, so callers see the array form everywhere except in the
     * database column itself.
     */
    protected $casts = [
        'credentials' => 'encrypted:array',
        'scopes' => 'array',
        'token_expires_at' => 'datetime:c',
        'created_at' => 'datetime:c',
        'updated_at' => 'datetime:c',
        'deleted_at' => 'datetime:c',
    ];

    /**
     * Never leak the encrypted credentials blob via array/JSON serialisation.
     * Callers that need a token must opt-in by reading the property
     * explicitly.
     */
    protected $hidden = [
        'credentials',
        'deleted_at',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * True when the stored token is past its expiry. A null expiry is treated
     * as "never expires" (some providers — e.g. long-lived personal access
     * tokens — never publish an expiry).
     */
    protected function isExpired(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->token_expires_at !== null
                && $this->token_expires_at->isPast(),
        );
    }
}
