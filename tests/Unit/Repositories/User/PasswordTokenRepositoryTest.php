<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Repositories\User;

use Polis\Tests\Fixtures\Models\PasswordToken;
use App\Models\User\User;
use Illuminate\Contracts\Events\Dispatcher;
use Mockery;
use Polis\Contracts\Services\TokenGenerationServiceContract;
use Polis\Events\User\ForgotPasswordEvent;
use Polis\Exceptions\NotImplementedException;
use Polis\Repositories\User\PasswordTokenRepository;
use Polis\Tests\TestCase;

/**
 * Coverage for PasswordTokenRepository — the event-dispatching create
 * override, the per-user findForUser lookup, and the unique-token
 * generation retry loop.
 *
 * The PasswordToken fixture is registered here (consumer-app namespace,
 * not yet aliased by tests/Fixtures/Models). We declare it inline so the
 * test is self-contained.
 */
final class PasswordTokenRepositoryTest extends TestCase
{
    public function test_create_dispatches_forgot_password_event_after_persist(): void
    {
        $modelMock = Mockery::mock(PasswordToken::class);
        $modelMock->shouldReceive('newInstance')->once()->andReturn($modelMock);
        $modelMock->shouldReceive('setAttribute');
        $modelMock->shouldReceive('getAttribute')->andReturn(7);
        $modelMock->wasRecentlyCreated = true;

        $dispatcher = Mockery::mock(Dispatcher::class);
        $dispatcher->shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($event) => $event instanceof ForgotPasswordEvent);

        $tokenService = Mockery::mock(TokenGenerationServiceContract::class);

        $repo = new PasswordTokenRepository($modelMock, $this->getGenericLogMock(), $dispatcher, $tokenService);
        $repo->create();
    }

    public function test_find_for_user_filters_by_user_id_and_token(): void
    {
        $user = new User;
        $user->id = 7;

        $expected = new PasswordToken;
        $query = Mockery::mock();
        $query->shouldReceive('where')->once()->with('user_id', '=', 7)->andReturnSelf();
        $query->shouldReceive('where')->once()->with('token', '=', 'tk-abc')->andReturnSelf();
        $query->shouldReceive('first')->once()->andReturn($expected);

        $modelMock = Mockery::mock(PasswordToken::class);
        $modelMock->shouldReceive('newQuery')->once()->andReturn($query);
        $modelMock->shouldReceive('setAttribute');
        $modelMock->shouldReceive('getAttribute')->andReturn(0);

        $dispatcher = Mockery::mock(Dispatcher::class);
        $tokenService = Mockery::mock(TokenGenerationServiceContract::class);

        $repo = new PasswordTokenRepository($modelMock, $this->getGenericLogMock(), $dispatcher, $tokenService);
        $this->assertSame($expected, $repo->findForUser($user, 'tk-abc'));
    }

    public function test_generate_unique_token_returns_token_on_first_try_when_no_collision(): void
    {
        $user = new User;
        $user->id = 7;

        $query = Mockery::mock();
        $query->shouldReceive('where')->andReturnSelf();
        $query->shouldReceive('first')->andReturn(null); // no collision

        $modelMock = Mockery::mock(PasswordToken::class);
        $modelMock->shouldReceive('newQuery')->andReturn($query);
        $modelMock->shouldReceive('setAttribute');
        $modelMock->shouldReceive('getAttribute')->andReturn(0);

        $dispatcher = Mockery::mock(Dispatcher::class);
        $tokenService = Mockery::mock(TokenGenerationServiceContract::class);
        $tokenService->shouldReceive('generateToken')->once()->andReturn('tok-001');

        $repo = new PasswordTokenRepository($modelMock, $this->getGenericLogMock(), $dispatcher, $tokenService);
        $this->assertSame('tok-001', $repo->generateUniqueToken($user));
    }

    public function test_generate_unique_token_retries_on_collision_until_unique(): void
    {
        $user = new User;
        $user->id = 7;

        $collisionCount = 0;
        $query = Mockery::mock();
        $query->shouldReceive('where')->andReturnSelf();
        $query->shouldReceive('first')->andReturnUsing(function () use (&$collisionCount) {
            $collisionCount++;
            // First two return a "collision", third returns null
            return $collisionCount <= 2 ? new PasswordToken : null;
        });

        $modelMock = Mockery::mock(PasswordToken::class);
        $modelMock->shouldReceive('newQuery')->andReturn($query);
        $modelMock->shouldReceive('setAttribute');
        $modelMock->shouldReceive('getAttribute')->andReturn(0);

        $dispatcher = Mockery::mock(Dispatcher::class);
        $tokenService = Mockery::mock(TokenGenerationServiceContract::class);
        $tokenService->shouldReceive('generateToken')->times(3)->andReturn('tok-a', 'tok-b', 'tok-c');

        $repo = new PasswordTokenRepository($modelMock, $this->getGenericLogMock(), $dispatcher, $tokenService);
        $this->assertSame('tok-c', $repo->generateUniqueToken($user));
    }

    public function test_generate_unique_token_throws_overflow_after_max_attempts(): void
    {
        $user = new User;
        $user->id = 7;

        $query = Mockery::mock();
        $query->shouldReceive('where')->andReturnSelf();
        $query->shouldReceive('first')->andReturn(new PasswordToken); // always collision

        $modelMock = Mockery::mock(PasswordToken::class);
        $modelMock->shouldReceive('newQuery')->andReturn($query);
        $modelMock->shouldReceive('setAttribute');
        $modelMock->shouldReceive('getAttribute')->andReturn(0);

        $dispatcher = Mockery::mock(Dispatcher::class);
        $tokenService = Mockery::mock(TokenGenerationServiceContract::class);
        $tokenService->shouldReceive('generateToken')->andReturn('any');

        $repo = new PasswordTokenRepository($modelMock, $this->getGenericLogMock(), $dispatcher, $tokenService);
        $this->expectException(\OverflowException::class);
        $repo->generateUniqueToken($user);
    }

    public function test_not_implemented_methods_throw(): void
    {
        $modelMock = Mockery::mock(PasswordToken::class);
        $modelMock->shouldReceive('setAttribute');
        $modelMock->shouldReceive('getAttribute')->andReturn(0);

        $dispatcher = Mockery::mock(Dispatcher::class);
        $tokenService = Mockery::mock(TokenGenerationServiceContract::class);

        $repo = new PasswordTokenRepository($modelMock, $this->getGenericLogMock(), $dispatcher, $tokenService);

        try {
            $repo->findAll();
            $this->fail('findAll should throw NotImplementedException');
        } catch (NotImplementedException $e) {
            $this->assertTrue(true);
        }

        try {
            $repo->findOrFail(1);
            $this->fail('findOrFail should throw NotImplementedException');
        } catch (NotImplementedException $e) {
            $this->assertTrue(true);
        }
    }
}
