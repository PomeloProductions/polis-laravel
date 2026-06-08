<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Repositories\User;

use Mockery;
use Polis\Contracts\Services\TokenGenerationServiceContract;
use Polis\Models\User\InvitationToken;
use Polis\Repositories\User\InvitationTokenRepository;
use Polis\Tests\TestCase;

/**
 * Coverage for InvitationTokenRepository — findByToken lookup and the
 * unique-token retry/overflow loop.
 *
 * InvitationToken is a Polis-namespaced model so there's no consumer-app
 * stub indirection here.
 */
final class InvitationTokenRepositoryTest extends TestCase
{
    public function test_find_by_token_filters_on_token_column(): void
    {
        $expected = new InvitationToken;

        $query = Mockery::mock();
        $query->shouldReceive('where')->once()->with('token', '=', 'abc')->andReturnSelf();
        $query->shouldReceive('first')->once()->andReturn($expected);

        $modelMock = Mockery::mock(InvitationToken::class);
        $modelMock->shouldReceive('newQuery')->once()->andReturn($query);
        $modelMock->shouldReceive('setAttribute');
        $modelMock->shouldReceive('getAttribute')->andReturn(0);

        $tokenService = Mockery::mock(TokenGenerationServiceContract::class);

        $repo = new InvitationTokenRepository($modelMock, $this->getGenericLogMock(), $tokenService);
        $this->assertSame($expected, $repo->findByToken('abc'));
    }

    public function test_generate_unique_token_returns_on_first_try(): void
    {
        $query = Mockery::mock();
        $query->shouldReceive('where')->andReturnSelf();
        $query->shouldReceive('first')->andReturn(null);

        $modelMock = Mockery::mock(InvitationToken::class);
        $modelMock->shouldReceive('newQuery')->andReturn($query);
        $modelMock->shouldReceive('setAttribute');
        $modelMock->shouldReceive('getAttribute')->andReturn(0);

        $tokenService = Mockery::mock(TokenGenerationServiceContract::class);
        $tokenService->shouldReceive('generateToken')->once()->andReturn('inv-1');

        $repo = new InvitationTokenRepository($modelMock, $this->getGenericLogMock(), $tokenService);
        $this->assertSame('inv-1', $repo->generateUniqueToken());
    }

    public function test_generate_unique_token_retries_on_collision(): void
    {
        $hits = 0;
        $query = Mockery::mock();
        $query->shouldReceive('where')->andReturnSelf();
        $query->shouldReceive('first')->andReturnUsing(function () use (&$hits) {
            return ++$hits <= 1 ? new InvitationToken : null;
        });

        $modelMock = Mockery::mock(InvitationToken::class);
        $modelMock->shouldReceive('newQuery')->andReturn($query);
        $modelMock->shouldReceive('setAttribute');
        $modelMock->shouldReceive('getAttribute')->andReturn(0);

        $tokenService = Mockery::mock(TokenGenerationServiceContract::class);
        $tokenService->shouldReceive('generateToken')->twice()->andReturn('a', 'b');

        $repo = new InvitationTokenRepository($modelMock, $this->getGenericLogMock(), $tokenService);
        $this->assertSame('b', $repo->generateUniqueToken());
    }

    public function test_generate_unique_token_throws_overflow_after_5_attempts(): void
    {
        $query = Mockery::mock();
        $query->shouldReceive('where')->andReturnSelf();
        $query->shouldReceive('first')->andReturn(new InvitationToken);

        $modelMock = Mockery::mock(InvitationToken::class);
        $modelMock->shouldReceive('newQuery')->andReturn($query);
        $modelMock->shouldReceive('setAttribute');
        $modelMock->shouldReceive('getAttribute')->andReturn(0);

        $tokenService = Mockery::mock(TokenGenerationServiceContract::class);
        $tokenService->shouldReceive('generateToken')->andReturn('x');

        $repo = new InvitationTokenRepository($modelMock, $this->getGenericLogMock(), $tokenService);
        $this->expectException(\OverflowException::class);
        $repo->generateUniqueToken();
    }
}
