<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Http\Core\Controllers;

use Illuminate\Http\JsonResponse;
use Mockery;
use Polis\Contracts\Repositories\User\PasswordTokenRepositoryContract;
use Polis\Contracts\Repositories\User\UserRepositoryContract;
use Polis\Tests\Fixtures\Controllers\ForgotPasswordController;
use Polis\Tests\Fixtures\Models\User as UserFixture;

/**
 * Unit coverage for ForgotPasswordControllerAbstract.
 *
 * Two routes: forgotPassword() generates + persists a token for the
 * lookup-by-email user; resetPassword() updates the password through
 * UserRepository::update. Both return {status: OK}.
 */
final class ForgotPasswordControllerAbstractTest extends ControllerTestCase
{
    public function test_forgot_password_generates_token_and_persists_via_repository(): void
    {
        $userRepo = Mockery::mock(UserRepositoryContract::class);
        $tokenRepo = Mockery::mock(PasswordTokenRepositoryContract::class);
        $user = Mockery::mock(UserFixture::class);

        $userRepo->shouldReceive('findByEmail')
            ->once()
            ->with('alice@example.test')
            ->andReturn($user);
        $tokenRepo->shouldReceive('generateUniqueToken')
            ->once()
            ->with($user)
            ->andReturn('opaque-token-abc');
        $tokenRepo->shouldReceive('create')
            ->once()
            ->with(['token' => 'opaque-token-abc'], $user);

        $request = $this->makeRequest(
            'Polis\\Http\\Core\\Requests\\ForgotPassword\\ForgotPasswordRequest',
            ['email' => 'alice@example.test'],
        );

        $response = (new ForgotPasswordController($userRepo, $tokenRepo))->forgotPassword($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(['status' => 'OK'], json_decode($response->getContent(), true));
    }

    public function test_reset_password_updates_user_password_via_repository(): void
    {
        $userRepo = Mockery::mock(UserRepositoryContract::class);
        $tokenRepo = Mockery::mock(PasswordTokenRepositoryContract::class);
        $user = Mockery::mock(UserFixture::class);

        $userRepo->shouldReceive('findByEmail')
            ->once()
            ->with('alice@example.test')
            ->andReturn($user);
        $userRepo->shouldReceive('update')
            ->once()
            ->with($user, ['password' => 'NewPassw0rd!'])
            ->andReturn($user);

        $request = $this->makeRequest(
            'Polis\\Http\\Core\\Requests\\ForgotPassword\\ResetPasswordRequest',
            ['email' => 'alice@example.test', 'password' => 'NewPassw0rd!', 'token' => 't'],
        );

        $response = (new ForgotPasswordController($userRepo, $tokenRepo))->resetPassword($request);

        $this->assertSame(['status' => 'OK'], json_decode($response->getContent(), true));
    }
}
