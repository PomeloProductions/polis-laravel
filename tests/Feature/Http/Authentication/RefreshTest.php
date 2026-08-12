<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\Http\Authentication;

use App\Models\User\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Polis\Tests\Application\ApplicationTestCase;
use Polis\Tests\Traits\MocksApplicationLog;

/**
 * Class RefreshTest
 */
final class RefreshTest extends ApplicationTestCase
{
    use MocksApplicationLog;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockApplicationLog();
        $this->setupDatabase();
    }

    public function test_token_refresh(): void
    {
        User::factory()->create([
            'email' => 'test@test.com',
            'password' => Hash::make('complex!'),
        ]);

        $loginResponse = $this->json('POST', '/v1/auth/login', [
            'email' => 'test@test.com',
            'password' => 'complex!',
        ]);
        $loginResponse->assertJsonStructure([
            'token',
        ]);
        $loginResponse->assertStatus(200);

        $token = $loginResponse->original['token'];

        $response = $this->json('POST', '/v1/auth/refresh', [], [
            'Authorization' => 'Bearer '.$token,
        ]);

        $response->assertJsonStructure(['token']);

        $token = $response->original['token'];

        $response = $this->json('GET', '/v1/users/me', [], [
            'Authorization' => $token,
        ]);

        $response->assertStatus(200);
    }

    public function test_token_refresh_after_refresh_window_fails(): void
    {
        User::factory()->create([
            'email' => 'test@test.com',
            'password' => Hash::make('complex!'),
        ]);

        $loginResponse = $this->json('POST', '/v1/auth/login', [
            'email' => 'test@test.com',
            'password' => 'complex!',
        ]);
        $loginResponse->assertJsonStructure([
            'token',
        ]);
        $loginResponse->assertStatus(200);

        $token = $loginResponse->original['token'];

        Carbon::setTestNow(Carbon::now()->addMonth(1)->addDay(1));
        $response = $this->json('POST', '/v1/auth/refresh', [], [
            'Authorization' => 'Bearer '.$token,
        ]);

        $response->assertStatus(401);
        $response->assertSimilarJson([
            'message' => 'Token has expired and can no longer be refreshed',
        ]);
    }

    public function test_token_refresh_after_expiration_before_refresh_time_succeeds(): void
    {
        User::factory()->create([
            'email' => 'test@test.com',
            'password' => Hash::make('complex!'),
        ]);

        $loginResponse = $this->json('POST', '/v1/auth/login', [
            'email' => 'test@test.com',
            'password' => 'complex!',
        ]);
        $loginResponse->assertJsonStructure([
            'token',
        ]);
        $loginResponse->assertStatus(200);

        $token = $loginResponse->original['token'];

        Carbon::setTestNow(Carbon::now()->addHours(2));
        $response = $this->json('POST', '/v1/auth/refresh', [], [
            'Authorization' => 'Bearer '.$token,
        ]);

        $response->assertJsonStructure(['token']);

        $newToken = $response->original['token'];

        $this->assertNotEquals($token, $newToken);

        $response = $this->json('GET', '/v1/users/me', [], [
            'Authorization' => $newToken,
        ]);

        $response->assertStatus(200);
    }
}
