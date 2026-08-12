<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Http\Core\Controllers;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Mockery;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use PHPOpenSourceSaver\JWTAuth\JWTAuth;
use Polis\Contracts\Repositories\User\InvitationTokenRepositoryContract;
use Polis\Contracts\Repositories\User\UserRepositoryContract;
use Polis\Events\User\InvitationAcceptedEvent;
use Polis\Events\User\SignUpEvent;
use Polis\Models\User\InvitationToken as PolisInvitationToken;
use Polis\Tests\Fixtures\Controllers\AuthenticationController;
use Polis\Tests\Fixtures\Models\User as UserFixture;

/**
 * Unit coverage for AuthenticationControllerAbstract.
 *
 * Four routes: login/refresh/signUp/logout. signUp() has the most
 * branches: it dispatches SignUpEvent always, and InvitationAcceptedEvent
 * only when an invitation_token was provided and the token exists.
 */
final class AuthenticationControllerAbstractTest extends ControllerTestCase
{
    public function test_login_returns_jwt_on_valid_credentials(): void
    {
        $auth = Mockery::mock(JWTAuth::class);
        $auth->shouldReceive('attempt')
            ->once()
            ->with(['email' => 'a@b.test', 'password' => 'pw'])
            ->andReturn('jwt-token-xyz');

        $request = $this->makeRequest(
            'Polis\\Http\\Core\\Requests\\Authentication\\LoginRequest',
            ['email' => 'a@b.test', 'password' => 'pw'],
        );

        $controller = new AuthenticationController(
            Mockery::mock(UserRepositoryContract::class),
            Mockery::mock(Hasher::class),
            $auth,
            Mockery::mock(Dispatcher::class),
            Mockery::mock(InvitationTokenRepositoryContract::class),
        );

        $response = $controller->login($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(['token' => 'jwt-token-xyz'], json_decode($response->getContent(), true));
    }

    public function test_login_throws_jwt_exception_on_invalid_credentials(): void
    {
        $auth = Mockery::mock(JWTAuth::class);
        $auth->shouldReceive('attempt')->once()->andReturn(false);

        $request = $this->makeRequest(
            'Polis\\Http\\Core\\Requests\\Authentication\\LoginRequest',
            ['email' => 'a@b.test', 'password' => 'wrong'],
        );

        $controller = new AuthenticationController(
            Mockery::mock(UserRepositoryContract::class),
            Mockery::mock(Hasher::class),
            $auth,
            Mockery::mock(Dispatcher::class),
            Mockery::mock(InvitationTokenRepositoryContract::class),
        );

        $this->expectException(JWTException::class);
        $controller->login($request);
    }

    public function test_sign_up_creates_user_dispatches_event_and_returns_token_201(): void
    {
        $userRepo = Mockery::mock(UserRepositoryContract::class);
        $hasher = Mockery::mock(Hasher::class);
        $auth = Mockery::mock(JWTAuth::class);
        $dispatcher = Mockery::mock(Dispatcher::class);
        $invitationTokenRepo = Mockery::mock(InvitationTokenRepositoryContract::class);

        $hasher->shouldReceive('make')->once()->with('plaintext')->andReturn('hashed-pw');

        $user = Mockery::mock(UserFixture::class);
        $userRepo->shouldReceive('create')
            ->once()
            ->with(
                ['email' => 'ada@x.test', 'password' => 'plaintext'],
                null,
                ['password' => 'hashed-pw'],
            )
            ->andReturn($user);

        $dispatcher->shouldReceive('dispatch')
            ->once()
            ->with(Mockery::type(SignUpEvent::class));

        $auth->shouldReceive('fromUser')->once()->with($user)->andReturn('jwt-token-new');

        $request = $this->makeRequest(
            'Polis\\Http\\Core\\Requests\\Authentication\\SignUpRequest',
            ['email' => 'ada@x.test', 'password' => 'plaintext'],
        );

        $controller = new AuthenticationController($userRepo, $hasher, $auth, $dispatcher, $invitationTokenRepo);
        $response = $controller->signUp($request);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame(['token' => 'jwt-token-new'], json_decode($response->getContent(), true));
    }

    public function test_sign_up_dispatches_invitation_accepted_event_when_token_provided_and_valid(): void
    {
        $userRepo = Mockery::mock(UserRepositoryContract::class);
        $hasher = Mockery::mock(Hasher::class);
        $auth = Mockery::mock(JWTAuth::class);
        $dispatcher = Mockery::mock(Dispatcher::class);
        $invitationTokenRepo = Mockery::mock(InvitationTokenRepositoryContract::class);

        $hasher->shouldReceive('make')->once()->andReturn('hashed');

        $user = Mockery::mock(UserFixture::class);
        $userRepo->shouldReceive('create')->once()->andReturn($user);

        $token = Mockery::mock(PolisInvitationToken::class);
        $invitationTokenRepo->shouldReceive('findByToken')
            ->once()
            ->with('invite-123')
            ->andReturn($token);

        $dispatcher->shouldReceive('dispatch')
            ->once()
            ->with(Mockery::type(SignUpEvent::class));
        $dispatcher->shouldReceive('dispatch')
            ->once()
            ->with(Mockery::type(InvitationAcceptedEvent::class));

        $auth->shouldReceive('fromUser')->once()->andReturn('jwt');

        $request = $this->makeRequest(
            'Polis\\Http\\Core\\Requests\\Authentication\\SignUpRequest',
            ['email' => 'a@b.test', 'password' => 'pw', 'invitation_token' => 'invite-123'],
        );

        $controller = new AuthenticationController($userRepo, $hasher, $auth, $dispatcher, $invitationTokenRepo);
        $controller->signUp($request);

        // assertions on the dispatcher expectations are auto-verified by Mockery on close()
        $this->addToAssertionCount(1);
    }

    public function test_refresh_returns_refreshed_token(): void
    {
        $auth = Mockery::mock(JWTAuth::class);
        $auth->shouldReceive('setRequest')->once()->andReturnSelf();
        $auth->shouldReceive('parseToken')->once()->andReturnSelf();
        $auth->shouldReceive('refresh')->once()->andReturn('jwt-refreshed');

        $request = new Request;

        $controller = new AuthenticationController(
            Mockery::mock(UserRepositoryContract::class),
            Mockery::mock(Hasher::class),
            $auth,
            Mockery::mock(Dispatcher::class),
            Mockery::mock(InvitationTokenRepositoryContract::class),
        );

        $response = $controller->refresh($request);

        $this->assertSame(['token' => 'jwt-refreshed'], json_decode($response->getContent(), true));
    }

    public function test_logout_invalidates_token_and_returns_status_ok(): void
    {
        $auth = Mockery::mock(JWTAuth::class);
        $auth->shouldReceive('getToken')->once()->andReturn('jwt-current');
        $auth->shouldReceive('invalidate')->once()->with('jwt-current');

        $controller = new AuthenticationController(
            Mockery::mock(UserRepositoryContract::class),
            Mockery::mock(Hasher::class),
            $auth,
            Mockery::mock(Dispatcher::class),
            Mockery::mock(InvitationTokenRepositoryContract::class),
        );

        $response = $controller->logout(new Request);

        $this->assertSame(['status' => 'ok'], json_decode($response->getContent(), true));
    }

    public function test_sign_up_skips_invitation_event_when_token_not_found(): void
    {
        $userRepo = Mockery::mock(UserRepositoryContract::class);
        $hasher = Mockery::mock(Hasher::class);
        $auth = Mockery::mock(JWTAuth::class);
        $dispatcher = Mockery::mock(Dispatcher::class);
        $invitationTokenRepo = Mockery::mock(InvitationTokenRepositoryContract::class);

        $hasher->shouldReceive('make')->once()->andReturn('hashed');

        $user = Mockery::mock(UserFixture::class);
        $userRepo->shouldReceive('create')->once()->andReturn($user);

        $invitationTokenRepo->shouldReceive('findByToken')
            ->once()
            ->with('stale')
            ->andReturn(null);

        $dispatcher->shouldReceive('dispatch')
            ->once()
            ->with(Mockery::type(SignUpEvent::class));
        // Critically, the InvitationAcceptedEvent dispatch is NOT made.
        $dispatcher->shouldNotReceive('dispatch')
            ->with(Mockery::type(InvitationAcceptedEvent::class));

        $auth->shouldReceive('fromUser')->once()->andReturn('jwt');

        $request = $this->makeRequest(
            'Polis\\Http\\Core\\Requests\\Authentication\\SignUpRequest',
            ['email' => 'a@b.test', 'password' => 'pw', 'invitation_token' => 'stale'],
        );

        $controller = new AuthenticationController($userRepo, $hasher, $auth, $dispatcher, $invitationTokenRepo);
        $controller->signUp($request);

        $this->addToAssertionCount(1);
    }
}
