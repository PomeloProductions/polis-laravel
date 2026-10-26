<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\Http\Authentication\TwoFactor;

use App\Models\User\User;
use Polis\Contracts\Services\TwoFactorAuthenticationServiceContract;
use Polis\Tests\Application\ApplicationTestCase;
use Polis\Tests\Traits\MocksApplicationLog;
use PragmaRX\Google2FA\Google2FA;

/**
 * Class TwoFactorEnrollTest
 *
 * POST /v1/auth/two-factor (enroll).
 */
final class TwoFactorEnrollTest extends ApplicationTestCase
{
    use MocksApplicationLog;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();
        $this->mockApplicationLog();
    }

    public function test_not_logged_in_blocked(): void
    {
        $response = $this->json('POST', '/v1/auth/two-factor');

        $response->assertStatus(403);
    }

    public function test_enroll_returns_secret_and_otpauth_url(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->json('POST', '/v1/auth/two-factor');

        $response->assertStatus(200);
        $response->assertJsonStructure(['secret', 'otpauth_url']);
        $this->assertStringStartsWith('otpauth://totp/', $response->json('otpauth_url'));
        $this->assertDatabaseHas('two_factor_secrets', ['user_id' => $user->id]);
    }

    public function test_enroll_while_already_confirmed_is_rejected(): void
    {
        $user = User::factory()->create();
        $service = $this->app->make(TwoFactorAuthenticationServiceContract::class);
        $secret = $service->enroll($user);
        $service->confirm($user, (new Google2FA)->getCurrentOtp($secret->secret));

        $this->actingAs($user);

        $response = $this->json('POST', '/v1/auth/two-factor');

        $response->assertStatus(422);
        $response->assertJson([
            'errors' => [
                'two_factor' => ['Two-factor authentication is already enabled.'],
            ],
        ]);
    }
}
