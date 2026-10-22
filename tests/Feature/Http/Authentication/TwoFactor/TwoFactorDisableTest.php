<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\Http\Authentication\TwoFactor;

use App\Models\User\User;
use Polis\Contracts\Services\TwoFactorAuthenticationServiceContract;
use Polis\Tests\Application\ApplicationTestCase;
use Polis\Tests\Traits\MocksApplicationLog;
use PragmaRX\Google2FA\Google2FA;

/**
 * Class TwoFactorDisableTest
 *
 * DELETE /v1/auth/two-factor.
 */
final class TwoFactorDisableTest extends ApplicationTestCase
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
        $response = $this->json('DELETE', '/v1/auth/two-factor');

        $response->assertStatus(403);
    }

    public function test_disable_removes_an_active_second_factor(): void
    {
        $user = User::factory()->create();
        $service = $this->app->make(TwoFactorAuthenticationServiceContract::class);
        $secret = $service->enroll($user);
        $service->confirm($user, (new Google2FA)->getCurrentOtp($secret->secret));
        $this->actingAs($user);

        $response = $this->json('DELETE', '/v1/auth/two-factor');

        $response->assertStatus(200);
        $response->assertExactJson(['status' => 'ok']);
        $this->assertFalse($service->isEnabled($user->refresh()));
    }

    public function test_disable_is_idempotent_when_not_enrolled(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->json('DELETE', '/v1/auth/two-factor');

        $response->assertStatus(200);
        $response->assertExactJson(['status' => 'ok']);
    }
}
