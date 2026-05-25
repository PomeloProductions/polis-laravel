<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Services;

use App\Models\User\User;
use Illuminate\Contracts\Hashing\Hasher;
use Polis\Contracts\Repositories\User\UserRepositoryContract;
use Polis\Exceptions\AuthenticationException;
use Polis\Services\UserAuthenticationService;
use Polis\Tests\TestCase;

/**
 * Class UserAuthenticationServiceTest
 */
class UserAuthenticationServiceTest extends TestCase
{
    public function test_retrieve_by_id()
    {
        $user = new User;
        $userRepositoryMock = mock(UserRepositoryContract::class);
        $userRepositoryMock->shouldReceive('findOrFail')->once()->with(12)->andReturn($user);
        $hasherMock = mock(Hasher::class);
        $service = new UserAuthenticationService($hasherMock, $userRepositoryMock);

        $this->assertEquals($user, $service->retrieveById(12));
    }

    public function test_retrieve_by_email_credential()
    {
        $user = new User;

        $userRepositoryMock = mock(UserRepositoryContract::class);
        $userRepositoryMock->shouldReceive('findByEmail')->once()->with('guy@smiley.com')->andReturn($user);
        $hasherMock = mock(Hasher::class);
        $service = new UserAuthenticationService($hasherMock, $userRepositoryMock);

        $this->assertEquals($user, $service->retrieveByCredentials(['email' => 'guy@smiley.com']));
    }

    public function test_retrieve_by_credential_missing_email_username_fails()
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('No valid identifying credential.');
        $userRepositoryMock = mock(UserRepositoryContract::class);
        $hasherMock = mock(Hasher::class);
        $service = new UserAuthenticationService($hasherMock, $userRepositoryMock);

        $service->retrieveByCredentials([]);
    }

    public function test_retrieve_by_credentials_empty_email_fails()
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('No valid identifying credential.');
        $userRepositoryMock = mock(UserRepositoryContract::class);
        $hasherMock = mock(Hasher::class);
        $service = new UserAuthenticationService($hasherMock, $userRepositoryMock);

        $service->retrieveByCredentials(['email' => '']);
    }
}
