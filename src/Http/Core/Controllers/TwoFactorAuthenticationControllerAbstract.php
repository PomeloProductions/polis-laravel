<?php

declare(strict_types=1);

namespace Polis\Http\Core\Controllers;

use App\Models\User\User;
use Illuminate\Http\JsonResponse;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use PHPOpenSourceSaver\JWTAuth\JWTAuth;
use Polis\Contracts\Repositories\User\UserRepositoryContract;
use Polis\Contracts\Services\TwoFactorAuthenticationServiceContract;
use Polis\Http\Core\Requests;

/**
 * Class TwoFactorAuthenticationControllerAbstract
 *
 * TOTP (authenticator-app) second-factor management + the login challenge
 * step. Consumers extend this with an empty concrete controller (exactly like
 * AuthenticationController) and wire the routes.
 *
 * Endpoints
 * ---------
 * Authenticated (JWT-protected), acting on auth()->user():
 *   GET    /auth/two-factor            status()  -> { enabled: bool }
 *   POST   /auth/two-factor            enroll()  -> { secret, otpauth_url }
 *   POST   /auth/two-factor/confirm    confirm() -> { recovery_codes: [...] }
 *   DELETE /auth/two-factor            disable() -> { status: "ok" }
 *
 * Public (completes a login that returned two_factor_required):
 *   POST   /auth/two-factor/verify     verify()  -> { token }
 */
abstract class TwoFactorAuthenticationControllerAbstract extends BaseControllerAbstract
{
    public function __construct(
        protected TwoFactorAuthenticationServiceContract $twoFactor,
        protected UserRepositoryContract $userRepository,
        protected JWTAuth $auth,
    ) {}

    /**
     * Report whether the authenticated user has an active second factor.
     */
    public function status(Requests\Authentication\TwoFactor\StatusRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        return new JsonResponse([
            'enabled' => $this->twoFactor->isEnabled($user),
        ]);
    }

    /**
     * Begin (or restart) enrollment. Returns the base32 secret (so the user
     * can type it in manually) and the otpauth:// URI the frontend renders as
     * a QR code. 2FA is NOT yet active until confirm() succeeds.
     */
    public function enroll(Requests\Authentication\TwoFactor\EnrollRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        // Re-enrolling while 2FA is already active would silently swap the
        // victim's authenticator if a live session were hijacked — require an
        // explicit disable first. Returned as a 422 so the frontend surfaces a
        // clean message rather than a 500.
        if ($this->twoFactor->isEnabled($user)) {
            return new JsonResponse([
                'errors' => [
                    'two_factor' => ['Two-factor authentication is already enabled.'],
                ],
            ], 422);
        }

        $secret = $this->twoFactor->enroll($user);

        return new JsonResponse([
            'secret' => $secret->secret,
            'otpauth_url' => $this->twoFactor->otpauthUri($user, $secret),
        ]);
    }

    /**
     * Confirm enrollment with a code from the authenticator app. On success
     * the account becomes 2FA-enabled and the one-time recovery codes are
     * returned — the ONLY time they are ever shown in plaintext. An invalid
     * code is a 422 so the frontend can surface it on the code field.
     */
    public function confirm(Requests\Authentication\TwoFactor\ConfirmRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        $recoveryCodes = $this->twoFactor->confirm($user, (string) $request->input('code'));

        if ($recoveryCodes === null) {
            return new JsonResponse([
                'errors' => [
                    'code' => ['The code is invalid or enrollment has not been started.'],
                ],
            ], 422);
        }

        return new JsonResponse([
            'recovery_codes' => $recoveryCodes,
        ]);
    }

    /**
     * Disable 2FA for the authenticated user. Idempotent.
     */
    public function disable(Requests\Authentication\TwoFactor\DisableRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        $this->twoFactor->disable($user);

        return new JsonResponse([
            'status' => 'ok',
        ]);
    }

    /**
     * Second step of a 2FA login. Resolves the challenge token issued by the
     * login endpoint, verifies the TOTP or recovery code, and mints the real
     * JWT on success. A bad/expired challenge or a wrong code is a 401 so it
     * is indistinguishable from any other failed login.
     *
     * @throws JWTException
     */
    public function verify(Requests\Authentication\TwoFactor\VerifyChallengeRequest $request): JsonResponse
    {
        $userId = $this->twoFactor->resolveChallengeToken((string) $request->input('challenge_token'));

        if ($userId === null) {
            throw new JWTException('Invalid or expired two-factor challenge.', 401);
        }

        /** @var User|null $user */
        $user = $this->userRepository->findOrFail($userId);

        if (! $this->twoFactor->verify($user, (string) $request->input('code'))) {
            throw new JWTException('Invalid two-factor code.', 401);
        }

        return new JsonResponse([
            'token' => $this->auth->fromUser($user),
        ]);
    }
}
