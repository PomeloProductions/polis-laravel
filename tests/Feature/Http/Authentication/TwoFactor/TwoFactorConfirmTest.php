<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\Http\Authentication\TwoFactor;

use App\Models\User\User;
use Polis\Contracts\Services\TwoFactorAuthenticationServiceContract;
use Polis\Services\TwoFactorAuthenticationService;
use Polis\Tests\Application\ApplicationTestCase;
use Polis\Tests\Traits\MocksApplicationLog;
use PragmaRX\Google2FA\Google2FA;

/**
 * Class TwoFactorConfirmTest
 *
 * POST /v1/auth/two-factor/confirm.
 */
final class TwoFactorConfirmTest extends ApplicationTestCase
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

    public function test_not_logged_in_blocked(): void
    {
        $response = $this->json('POST', '/v1/auth/two-factor/confirm', ['code' => '123456']);

        $response->assertStatus(403);
    }

    public function test_missing_required_fields(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->json('POST', '/v1/auth/two-factor/confirm');

        $response->assertStatus(422);
        $response->assertJson([
            'errors' => [
                'code' => ['The code field is required.'],
            ],
        ]);
    }

    public function test_code_too_long(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->json('POST', '/v1/auth/two-factor/confirm', [
            'code' => str_repeat('1', 17),
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'errors' => [
                'code' => ['The code may not be greater than 16 characters.'],
            ],
        ]);
    }

    public function test_invalid_code_returns_422(): void
    {
        $user = User::factory()->create();
        $this->service->enroll($user);
        $this->actingAs($user);

        $response = $this->json('POST', '/v1/auth/two-factor/confirm', ['code' => '000000']);

        $response->assertStatus(422);
        $response->assertJsonStructure(['errors' => ['code']]);
    }

    public function test_valid_code_confirms_and_returns_recovery_codes(): void
    {
        $user = User::factory()->create();
        $secret = $this->service->enroll($user);
        $this->actingAs($user);

        $response = $this->json('POST', '/v1/auth/two-factor/confirm', [
            'code' => $this->google2fa->getCurrentOtp($secret->secret),
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['recovery_codes']);
        $this->assertCount(
            TwoFactorAuthenticationService::RECOVERY_CODE_COUNT,
            $response->json('recovery_codes'),
        );
        $this->assertTrue($this->service->isEnabled($user->refresh()));
    }
}
