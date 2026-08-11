<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\Http\Authentication;

use App\Models\User\User;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Polis\Http\Middleware\LogMiddleware;
use Polis\Tests\Application\ApplicationTestCase;
use Polis\Tests\Traits\MocksApplicationLog;

/**
 * Class LogoutTest
 */
final class LogoutTest extends ApplicationTestCase
{
    use MocksApplicationLog;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockApplicationLog();
        $this->setupDatabase();
    }

    public function test_logout(): void
    {
        $this->app['env'] = 'testing-override';  // @todo fix this
        $this->app->instance(LogMiddleware::class, new class
        {
            public function handle($request, $next)
            {
                return $next($request);
            }
        });

        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);
        $response = $this->json('POST', '/v1/auth/logout', [], ['Authorization' => 'Bearer '.$token]);
        $this->app['env'] = 'testing'; // @todo resolve
        $response->assertStatus(200);
        JWTAuth::authenticate($token);
    }
}
