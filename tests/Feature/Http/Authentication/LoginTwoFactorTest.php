<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\Http\Authentication;

use App\Models\User\User;
use Illuminate\Support\Facades\Hash;
use Polis\Contracts\Services\TwoFactorAuthenticationServiceContract;
use Polis\Tests\Application\ApplicationTestCase;
use Polis\Tests\Traits\MocksApplicationLog;
use PragmaRX\Google2FA\Google2FA;

/**
 * Class LoginTwoFactorTest
 *
 * The login endpoint's 2FA branch: a user WITHOUT a confirmed second factor
 * still gets a token directly (the pre-existing contract), while a 2FA-enabled
 * user gets a challenge instead and must complete the second step.
 */
final class LoginTwoFactorTest extends ApplicationTestCase
{
    use MocksApplicationLog;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();
        $this->mockApplicationLog();
    }

    public function test_login_without_two_factor_returns_token_directly(): void
    {
        User::factory()->create([
            'email' => 'no2fa@example.com',
            'password' => Hash::make('complex!'),
        ]);

        $response = $this->json('POST', '/v1/auth/login', [
            'email' => 'no2fa@example.com',
            'password' => 'complex!',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['token']);
        $response->assertJsonMissingPath('two_factor_required');
    }

    public function test_login_with_two_factor_returns_a_challenge_not_a_token(): void
    {
        /** @var User $user */
        $user = User::factory()->create([
            'email' => 'has2fa@example.com',
            'password' => Hash::make('complex!'),
        ]);

        $service = $this->app->make(TwoFactorAuthenticationServiceContract::class);
        $secret = $service->enroll($user);
        $service->confirm($user, (new Google2FA)->getCurrentOtp($secret->secret));

        $response = $this->json('POST', '/v1/auth/login', [
            'email' => 'has2fa@example.com',
            'password' => 'complex!',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['two_factor_required' => true]);
        $response->assertJsonStructure(['challenge_token']);
        $response->assertJsonMissingPath('token');
    }

    public function test_wrong_password_still_401_for_two_factor_user(): void
    {
        /** @var User $user */
        $user = User::factory()->create([
            'email' => 'has2fa2@example.com',
            'password' => Hash::make('complex!'),
        ]);

        $service = $this->app->make(TwoFactorAuthenticationServiceContract::class);
        $secret = $service->enroll($user);
        $service->confirm($user, (new Google2FA)->getCurrentOtp($secret->secret));

        $response = $this->json('POST', '/v1/auth/login', [
            'email' => 'has2fa2@example.com',
            'password' => 'wrong!',
        ]);

        $response->assertStatus(401);
        $response->assertJson(['message' => 'Invalid login credentials.']);
    }
}
