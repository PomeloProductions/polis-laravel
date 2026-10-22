<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\Http\Authentication\TwoFactor;

use App\Models\User\User;
use Polis\Contracts\Services\TwoFactorAuthenticationServiceContract;
use Polis\Tests\Application\ApplicationTestCase;
use Polis\Tests\Traits\MocksApplicationLog;
use PragmaRX\Google2FA\Google2FA;

/**
 * Class TwoFactorVerifyChallengeTest
 *
 * POST /v1/auth/two-factor/verify — the second step of a 2FA login.
 */
final class TwoFactorVerifyChallengeTest extends ApplicationTestCase
{
    use MocksApplicationLog;

    private TwoFactorAuthenticationServiceContract $service;

    private Google2FA $google2fa;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();
        $this->mockApplicationLog();

        $this->service = $this->app->make(TwoFactorAuthenticationServiceContract::class);
        $this->google2fa = new Google2FA;
    }

    /**
     * Create a user with a confirmed second factor and return [user, secret].
     *
     * @return array{0: User, 1: string}
     */
    private function userWithTwoFactor(): array
    {
        $user = User::factory()->create();
        $secret = $this->service->enroll($user);
        $this->service->confirm($user, $this->google2fa->getCurrentOtp($secret->secret));

        return [$user, $secret->secret];
    }

    public function test_missing_required_fields(): void
    {
        $response = $this->json('POST', '/v1/auth/two-factor/verify');

        $response->assertStatus(422);
        $response->assertJson([
            'errors' => [
                'challenge_token' => ['The challenge token field is required.'],
                'code' => ['The code field is required.'],
            ],
        ]);
    }

    public function test_code_too_long(): void
    {
        $response = $this->json('POST', '/v1/auth/two-factor/verify', [
            'challenge_token' => 'anything',
            'code' => str_repeat('1', 17),
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'errors' => [
                'code' => ['The code may not be greater than 16 characters.'],
            ],
        ]);
    }

    public function test_invalid_challenge_token_returns_401(): void
    {
        $response = $this->json('POST', '/v1/auth/two-factor/verify', [
            'challenge_token' => 'not-a-valid-token',
            'code' => '123456',
        ]);

        $response->assertStatus(401);
        $response->assertJson(['message' => 'Invalid or expired two-factor challenge.']);
    }

    public function test_wrong_code_returns_401(): void
    {
        [$user] = $this->userWithTwoFactor();
        $challenge = $this->service->issueChallengeToken($user);

        $response = $this->json('POST', '/v1/auth/two-factor/verify', [
            'challenge_token' => $challenge,
            'code' => '000000',
        ]);

        $response->assertStatus(401);
        $response->assertJson(['message' => 'Invalid two-factor code.']);
    }

    public function test_valid_totp_code_mints_a_token(): void
    {
        [$user, $secret] = $this->userWithTwoFactor();
        $challenge = $this->service->issueChallengeToken($user);

        $response = $this->json('POST', '/v1/auth/two-factor/verify', [
            'challenge_token' => $challenge,
            'code' => $this->google2fa->getCurrentOtp($secret),
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['token']);
    }

    public function test_valid_recovery_code_mints_a_token(): void
    {
        $user = User::factory()->create();
        $secret = $this->service->enroll($user);
        $recoveryCodes = $this->service->confirm($user, $this->google2fa->getCurrentOtp($secret->secret));
        $challenge = $this->service->issueChallengeToken($user);

        $response = $this->json('POST', '/v1/auth/two-factor/verify', [
            'challenge_token' => $challenge,
            'code' => $recoveryCodes[0],
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['token']);
    }
}
