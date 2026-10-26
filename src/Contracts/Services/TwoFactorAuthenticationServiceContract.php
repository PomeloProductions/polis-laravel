<?php

declare(strict_types=1);

namespace Polis\Contracts\Services;

use App\Models\User\User;
use Polis\Exceptions\AuthenticationException;
use Polis\Models\User\TwoFactorSecret;

/**
 * Interface TwoFactorAuthenticationServiceContract
 *
 * The TOTP (RFC 6238) second-factor domain service. Owns secret generation,
 * the otpauth:// provisioning URI, code verification (with clock-drift
 * tolerance), recovery-code issuance/consumption and the short-lived login
 * challenge token. The HTTP controller is a thin wrapper over this.
 */
interface TwoFactorAuthenticationServiceContract
{
    /**
     * Begin (or restart) TOTP enrollment for a user.
     *
     * Generates a fresh base32 secret, persists it PENDING (confirmed_at null,
     * no recovery codes yet) replacing any prior unconfirmed secret, and
     * returns the created model. The raw secret and the otpauth URI for the QR
     * code are read off the returned model via {@see otpauthUri()}.
     *
     * If the user already has a CONFIRMED secret this throws — callers must
     * disable the existing factor before re-enrolling, so an attacker who
     * hijacks a live session cannot silently swap the victim's authenticator.
     *
     * @throws AuthenticationException when 2FA is already confirmed
     */
    public function enroll(User $user): TwoFactorSecret;

    /**
     * Complete enrollment by verifying a code from the user's authenticator
     * app against the pending secret.
     *
     * On success: marks the secret confirmed, generates the single-use
     * recovery codes, stores them hashed, and returns the PLAINTEXT codes
     * (the only time they are ever visible). On failure returns null and the
     * secret stays pending.
     *
     * @return string[]|null the plaintext recovery codes, or null if the code was invalid
     */
    public function confirm(User $user, string $code): ?array;

    /**
     * Verify a TOTP code OR a recovery code for a user with a confirmed
     * secret. A matched recovery code is consumed (removed) so it cannot be
     * reused. Returns true when either the TOTP code is valid for the current
     * window (±1 step drift) or a recovery code matched.
     */
    public function verify(User $user, string $code): bool;

    /**
     * Whether the user has a CONFIRMED (active) second factor and must
     * therefore pass {@see verify()} before a JWT is issued at login.
     */
    public function isEnabled(User $user): bool;

    /**
     * Fully disable 2FA for the user: deletes the secret and recovery codes.
     * Idempotent — a no-op when the user has no secret.
     */
    public function disable(User $user): void;

    /**
     * Build the otpauth:// provisioning URI for a (pending or confirmed)
     * secret. The frontend renders this as a QR code; the `issuer` labels the
     * entry in the user's authenticator app (defaults to the configured app
     * name).
     */
    public function otpauthUri(User $user, TwoFactorSecret $secret): string;

    /**
     * Mint a short-lived, signed login challenge token that encodes the user
     * id. Returned by the login endpoint when a password check succeeds but a
     * second factor is still required; the client echoes it back to the
     * two-factor verify endpoint. Opaque and single-purpose — it is NOT a JWT
     * session token and grants no access on its own.
     */
    public function issueChallengeToken(User $user): string;

    /**
     * Resolve the user id embedded in a challenge token, or null when the
     * token is missing, malformed, tampered with, or expired.
     */
    public function resolveChallengeToken(string $challengeToken): ?int;
}
