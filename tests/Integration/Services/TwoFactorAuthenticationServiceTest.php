<?php

declare(strict_types=1);

namespace Polis\Tests\Integration\Services;

use App\Models\User\User;
use Polis\Contracts\Services\TwoFactorAuthenticationServiceContract;
use Polis\Exceptions\AuthenticationException;
use Polis\Models\User\TwoFactorSecret;
use Polis\Services\TwoFactorAuthenticationService;
use Polis\Tests\Application\ApplicationTestCase;
use Polis\Tests\Traits\MocksApplicationLog;
use PragmaRX\Google2FA\Google2FA;

/**
 * Class TwoFactorAuthenticationServiceTest
 *
 * Exercises the TOTP second-factor domain service end-to-end against the real
 * two_factor_secrets table and the real google2fa engine, through the
 * container binding a consumer resolves.
 */
final class TwoFactorAuthenticationServiceTest extends ApplicationTestCase
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

    private function codeFor(string $secret): string
    {
        return $this->google2fa->getCurrentOtp($secret);
    }

    public function test_enroll_creates_a_pending_secret(): void
    {
        $user = User::factory()->create();

        $secret = $this->service->enroll($user);

        $this->assertNotEmpty($secret->secret);
        $this->assertNull($secret->confirmed_at);
        $this->assertFalse($this->service->isEnabled($user));
        $this->assertDatabaseHas('two_factor_secrets', ['user_id' => $user->id]);
    }

    public function test_enroll_replaces_a_prior_pending_secret(): void
    {
        $user = User::factory()->create();

        $first = $this->service->enroll($user);
        $second = $this->service->enroll($user);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, TwoFactorSecret::query()->where('user_id', $user->id)->count());
    }

    public function test_enroll_throws_when_already_confirmed(): void
    {
        $user = User::factory()->create();
        $secret = $this->service->enroll($user);
        $this->service->confirm($user, $this->codeFor($secret->secret));

        $this->expectException(AuthenticationException::class);
        $this->service->enroll($user);
    }

    public function test_confirm_with_valid_code_enables_and_returns_recovery_codes(): void
    {
        $user = User::factory()->create();
        $secret = $this->service->enroll($user);

        $recoveryCodes = $this->service->confirm($user, $this->codeFor($secret->secret));

        $this->assertIsArray($recoveryCodes);
        $this->assertCount(TwoFactorAuthenticationService::RECOVERY_CODE_COUNT, $recoveryCodes);
        $this->assertTrue($this->service->isEnabled($user));
    }

    public function test_confirm_with_invalid_code_returns_null_and_stays_pending(): void
    {
        $user = User::factory()->create();
        $this->service->enroll($user);

        $result = $this->service->confirm($user, '000000');

        $this->assertNull($result);
        $this->assertFalse($this->service->isEnabled($user));
    }

    public function test_confirm_without_enrollment_returns_null(): void
    {
        $user = User::factory()->create();

        $this->assertNull($this->service->confirm($user, '123456'));
    }

    public function test_verify_accepts_a_valid_totp_code(): void
    {
        $user = User::factory()->create();
        $secret = $this->service->enroll($user);
        $this->service->confirm($user, $this->codeFor($secret->secret));

        $this->assertTrue($this->service->verify($user, $this->codeFor($secret->secret)));
    }

    public function test_verify_rejects_an_invalid_code(): void
    {
        $user = User::factory()->create();
        $secret = $this->service->enroll($user);
        $this->service->confirm($user, $this->codeFor($secret->secret));

        $this->assertFalse($this->service->verify($user, '000000'));
    }

    public function test_verify_returns_false_when_not_enabled(): void
    {
        $user = User::factory()->create();

        $this->assertFalse($this->service->verify($user, '123456'));
    }

    public function test_recovery_code_is_single_use(): void
    {
        $user = User::factory()->create();
        $secret = $this->service->enroll($user);
        $recoveryCodes = $this->service->confirm($user, $this->codeFor($secret->secret));

        $code = $recoveryCodes[0];

        $this->assertTrue($this->service->verify($user, $code));
        // Second use of the same recovery code must fail.
        $this->assertFalse($this->service->verify($user, $code));
    }

    public function test_disable_removes_the_secret(): void
    {
        $user = User::factory()->create();
        $secret = $this->service->enroll($user);
        $this->service->confirm($user, $this->codeFor($secret->secret));

        $this->service->disable($user);

        $this->assertFalse($this->service->isEnabled($user));
    }

    public function test_disable_is_idempotent_without_a_secret(): void
    {
        $user = User::factory()->create();

        $this->service->disable($user);

        $this->assertFalse($this->service->isEnabled($user));
    }

    public function test_challenge_token_round_trips_the_user_id(): void
    {
        $user = User::factory()->create();

        $token = $this->service->issueChallengeToken($user);

        $this->assertSame($user->id, $this->service->resolveChallengeToken($token));
    }

    public function test_resolve_challenge_token_rejects_garbage(): void
    {
        $this->assertNull($this->service->resolveChallengeToken('not-a-real-token'));
    }

    public function test_otpauth_uri_contains_issuer_and_secret(): void
    {
        $user = User::factory()->create(['email' => 'totp@example.com']);
        $secret = $this->service->enroll($user);

        $uri = $this->service->otpauthUri($user, $secret);

        $this->assertStringStartsWith('otpauth://totp/', $uri);
        $this->assertStringContainsString('secret='.$secret->secret, $uri);
    }
}
