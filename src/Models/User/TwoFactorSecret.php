<?php

declare(strict_types=1);

namespace Polis\Models\User;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Polis\Models\BaseModelAbstract;

/**
 * Class TwoFactorSecret
 *
 * Stores a user's TOTP (time-based one-time password) second-factor secret and
 * the hashed, single-use recovery codes that let them back in if they lose the
 * authenticator device.
 *
 * Enrollment is a two-step handshake:
 *  1. enroll()  — generates a fresh base32 secret, persists it with
 *     `confirmed_at = null` (PENDING) and returns the otpauth:// URI the
 *     frontend renders as a QR code.
 *  2. confirm() — the user types a code from their authenticator app; on a
 *     valid code `confirmed_at` is set and recovery codes are issued. Only a
 *     CONFIRMED secret makes the account 2FA-enabled, so the login flow must
 *     check {@see self::isConfirmed()} before challenging.
 *
 * Secret storage is encrypted-at-rest. The raw base32 seed is the shared
 * secret that anyone with it can use to mint valid codes indefinitely, so it is
 * stored via Laravel's `encrypted` cast (Crypt::encryptString under APP_KEY)
 * and never appears in plaintext in the database — matching the
 * {@see ExternalAccountConnection} precedent. Recovery codes are individually
 * bcrypt-hashed before being stored, and the whole array is additionally
 * encrypted as defence-in-depth.
 *
 * @property int $id
 * @property int $user_id
 * @property string $secret The decrypted base32 TOTP seed.
 * @property array|null $recovery_codes Decrypted array of bcrypt-hashed recovery codes.
 * @property Carbon|null $confirmed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read bool $is_confirmed
 * @property-read User $user
 */
class TwoFactorSecret extends BaseModelAbstract
{
    /**
     * Encrypted-at-rest casts. `encrypted` runs Crypt::encryptString on write
     * and decryptString on read; `encrypted:array` additionally JSON-encodes.
     * Callers see the plaintext secret / decoded array everywhere except the
     * database column itself.
     */
    protected $casts = [
        'secret' => 'encrypted',
        'recovery_codes' => 'encrypted:array',
        'confirmed_at' => 'datetime:c',
        'created_at' => 'datetime:c',
        'updated_at' => 'datetime:c',
        'deleted_at' => 'datetime:c',
    ];

    /**
     * Never leak the secret or the (hashed) recovery codes through array/JSON
     * serialisation. The enroll/confirm controller returns exactly the fields
     * the client needs (the otpauth URI, the one-time plaintext recovery
     * codes) explicitly — the persisted row itself is opaque to the API.
     */
    protected $hidden = [
        'secret',
        'recovery_codes',
        'deleted_at',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Whether this secret has completed the confirm() handshake and therefore
     * makes the account 2FA-enabled. A pending (unconfirmed) secret must NOT
     * cause the login flow to challenge for a second factor.
     */
    protected function isConfirmed(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => $this->confirmed_at !== null,
        );
    }
}
