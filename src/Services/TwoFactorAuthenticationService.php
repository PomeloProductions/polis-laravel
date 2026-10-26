<?php

declare(strict_types=1);

namespace Polis\Services;

use App\Models\User\User;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Support\Str;
use Polis\Contracts\Repositories\User\TwoFactorSecretRepositoryContract;
use Polis\Contracts\Services\TwoFactorAuthenticationServiceContract;
use Polis\Exceptions\AuthenticationException;
use Polis\Models\User\TwoFactorSecret;
use PragmaRX\Google2FA\Google2FA;

/**
 * Class TwoFactorAuthenticationService
 *
 * Concrete TOTP (RFC 6238) second-factor service. See
 * {@see TwoFactorAuthenticationServiceContract} for the contract documentation.
 *
 * Library choice — pragmarx/google2fa
 * -----------------------------------
 * google2fa is the de-facto maintained PHP TOTP implementation (the same
 * engine Laravel Fortify wraps), framework-agnostic and with no session/guard
 * assumptions, so it slots cleanly into this JWT-based package without pulling
 * in Fortify's session machinery. It handles base32 secret generation,
 * otpauth-compatible code verification and a configurable drift window.
 */
class TwoFactorAuthenticationService implements TwoFactorAuthenticationServiceContract
{
    /**
     * Number of single-use recovery codes issued on confirmation. Eight is the
     * industry norm (GitHub, Google) — enough to print/store a sheet without
     * being unwieldy.
     */
    public const RECOVERY_CODE_COUNT = 8;

    /**
     * Clock-drift tolerance in 30-second steps. 1 means a code from the
     * previous/next window still validates, covering phone/server clock skew
     * without materially widening the attack surface.
     */
    public const VERIFY_WINDOW = 1;

    /**
     * Lifetime of a login challenge token. The user has this long after a
     * correct password to produce a second factor before they must log in
     * again.
     */
    public const CHALLENGE_TTL_SECONDS = 300;

    public function __construct(
        private Google2FA $google2fa,
        private TwoFactorSecretRepositoryContract $repository,
        private Hasher $hasher,
        private Encrypter $encrypter,
        private Config $config,
    ) {}

    public function enroll(User $user): TwoFactorSecret
    {
        $existing = $this->repository->findForUser($user);

        if ($existing && $existing->confirmed_at !== null) {
            throw new AuthenticationException('Two-factor authentication is already enabled.');
        }

        $secret = $this->google2fa->generateSecretKey();

        // Replace any prior pending secret so a fresh QR is always issued.
        if ($existing) {
            return $this->repository->update($existing, [], [
                'secret' => $secret,
                'recovery_codes' => null,
                'confirmed_at' => null,
            ]);
        }

        return $this->repository->create([], null, [
            'user_id' => $user->id,
            'secret' => $secret,
        ]);
    }

    public function confirm(User $user, string $code): ?array
    {
        $secret = $this->repository->findForUser($user);

        if (! $secret) {
            return null;
        }

        if (! $this->google2fa->verifyKey($secret->secret, $code, self::VERIFY_WINDOW)) {
            return null;
        }

        $plainCodes = $this->generateRecoveryCodes();
        $hashedCodes = array_map(fn (string $c): string => $this->hasher->make($c), $plainCodes);

        $this->repository->update($secret, [], [
            'recovery_codes' => $hashedCodes,
            'confirmed_at' => now(),
        ]);

        return $plainCodes;
    }

    public function verify(User $user, string $code): bool
    {
        $secret = $this->repository->findConfirmedForUser($user);

        if (! $secret) {
            return false;
        }

        if ($this->google2fa->verifyKey($secret->secret, $code, self::VERIFY_WINDOW)) {
            return true;
        }

        return $this->consumeRecoveryCode($secret, $code);
    }

    public function isEnabled(User $user): bool
    {
        return $this->repository->findConfirmedForUser($user) !== null;
    }

    public function disable(User $user): void
    {
        $secret = $this->repository->findForUser($user);

        if ($secret) {
            $this->repository->delete($secret);
        }
    }

    public function otpauthUri(User $user, TwoFactorSecret $secret): string
    {
        $issuer = (string) $this->config->get('app.name', 'Polis');

        return $this->google2fa->getQRCodeUrl(
            $issuer,
            $user->getEmailAddress(),
            $secret->secret,
        );
    }

    public function issueChallengeToken(User $user): string
    {
        return $this->encrypter->encrypt([
            'two_factor_challenge' => true,
            'user_id' => $user->id,
            'expires_at' => now()->addSeconds(self::CHALLENGE_TTL_SECONDS)->getTimestamp(),
        ]);
    }

    public function resolveChallengeToken(string $challengeToken): ?int
    {
        try {
            $payload = $this->encrypter->decrypt($challengeToken);
        } catch (\Throwable) {
            return null;
        }

        if (! is_array($payload)
            || ($payload['two_factor_challenge'] ?? null) !== true
            || ! isset($payload['user_id'], $payload['expires_at'])) {
            return null;
        }

        if ((int) $payload['expires_at'] < now()->getTimestamp()) {
            return null;
        }

        return (int) $payload['user_id'];
    }

    /**
     * Find and remove a matching recovery code. Recovery codes are stored
     * bcrypt-hashed, so every stored hash is checked against the supplied
     * code; a match is removed (single-use) and the row re-persisted.
     */
    private function consumeRecoveryCode(TwoFactorSecret $secret, string $code): bool
    {
        $codes = $secret->recovery_codes ?? [];
        $normalized = trim($code);

        foreach ($codes as $index => $hash) {
            if ($this->hasher->check($normalized, $hash)) {
                unset($codes[$index]);
                $this->repository->update($secret, [], [
                    'recovery_codes' => array_values($codes),
                ]);

                return true;
            }
        }

        return false;
    }

    /**
     * @return string[] a fresh set of human-friendly recovery codes
     */
    private function generateRecoveryCodes(): array
    {
        $codes = [];
        for ($i = 0; $i < self::RECOVERY_CODE_COUNT; $i++) {
            // 10-char base32-ish, dash-grouped for readability (e.g. ABCDE-FGHIJ).
            $codes[] = strtoupper(Str::random(5).'-'.Str::random(5));
        }

        return $codes;
    }
}
