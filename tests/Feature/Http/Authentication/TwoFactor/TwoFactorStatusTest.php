<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\Http\Authentication\TwoFactor;

use App\Models\User\User;
use Polis\Contracts\Services\TwoFactorAuthenticationServiceContract;
use Polis\Tests\Application\ApplicationTestCase;
use Polis\Tests\Traits\MocksApplicationLog;
use PragmaRX\Google2FA\Google2FA;

/**
 * Class TwoFactorStatusTest
 *
 * GET /v1/auth/two-factor.
 */
final class TwoFactorStatusTest extends ApplicationTestCase
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
        $response = $this->json('GET', '/v1/auth/two-factor');

        $response->assertStatus(403);
    }

    public function test_status_false_when_not_enrolled(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->json('GET', '/v1/auth/two-factor');

        $response->assertStatus(200);
        $response->assertExactJson(['enabled' => false]);
    }

    public function test_status_false_when_only_pending(): void
    {
        $user = User::factory()->create();
        $this->app->make(TwoFactorAuthenticationServiceContract::class)->enroll($user);
        $this->actingAs($user);

        $response = $this->json('GET', '/v1/auth/two-factor');

        $response->assertStatus(200);
        $response->assertExactJson(['enabled' => false]);
    }

    public function test_status_true_when_confirmed(): void
    {
        $user = User::factory()->create();
        $service = $this->app->make(TwoFactorAuthenticationServiceContract::class);
        $secret = $service->enroll($user);
        $service->confirm($user, (new Google2FA)->getCurrentOtp($secret->secret));
        $this->actingAs($user);

        $response = $this->json('GET', '/v1/auth/two-factor');

        $response->assertStatus(200);
        $response->assertExactJson(['enabled' => true]);
    }
}
