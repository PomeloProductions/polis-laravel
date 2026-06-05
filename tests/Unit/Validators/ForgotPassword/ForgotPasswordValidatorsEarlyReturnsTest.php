<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Validators\ForgotPassword;

use Illuminate\Http\Request;
use Mockery;
use Polis\Contracts\Repositories\User\PasswordTokenRepositoryContract;
use Polis\Contracts\Repositories\User\UserRepositoryContract;
use Polis\Tests\TestCase;
use Polis\Validators\ForgotPassword\TokenIsNotExpiredValidator;
use Polis\Validators\ForgotPassword\UserOwnsTokenValidator;

/**
 * Standalone-runnable coverage for the missing-email and missing-user
 * short-circuits in both ForgotPassword validators. Full success-path
 * coverage (with a real PasswordToken instance) lives in Consumer-Only —
 * PasswordToken extends Polis\Models\BaseModelAbstract which pulls in
 * the AdminUI EloquentJoin trait absent from this package's standalone
 * test environment.
 */
final class ForgotPasswordValidatorsEarlyReturnsTest extends TestCase
{
    public function test_token_is_not_expired_returns_false_when_no_email_in_request(): void
    {
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('input')->with('email', null)->andReturn(null);

        $userRepo = Mockery::mock(UserRepositoryContract::class);
        $userRepo->shouldNotReceive('findByEmail');

        $tokenRepo = Mockery::mock(PasswordTokenRepositoryContract::class);

        $validator = new TokenIsNotExpiredValidator($request, $userRepo, $tokenRepo);

        $this->assertFalse($validator->validate('token', 'some-token-value'));
    }

    public function test_token_is_not_expired_throws_when_wrong_attribute(): void
    {
        $request = Mockery::mock(Request::class);
        $userRepo = Mockery::mock(UserRepositoryContract::class);
        $tokenRepo = Mockery::mock(PasswordTokenRepositoryContract::class);

        $validator = new TokenIsNotExpiredValidator($request, $userRepo, $tokenRepo);

        $this->expectException(\RuntimeException::class);

        $validator->validate('wrong_field', 'value');
    }

    public function test_user_owns_token_returns_false_when_no_email_in_request(): void
    {
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('input')->with('email', null)->andReturn(null);

        $userRepo = Mockery::mock(UserRepositoryContract::class);
        $userRepo->shouldNotReceive('findByEmail');

        $tokenRepo = Mockery::mock(PasswordTokenRepositoryContract::class);

        $validator = new UserOwnsTokenValidator($request, $userRepo, $tokenRepo);

        $this->assertFalse($validator->validate('token', 'some-token-value'));
    }

    public function test_user_owns_token_throws_when_wrong_attribute(): void
    {
        $request = Mockery::mock(Request::class);
        $userRepo = Mockery::mock(UserRepositoryContract::class);
        $tokenRepo = Mockery::mock(PasswordTokenRepositoryContract::class);

        $validator = new UserOwnsTokenValidator($request, $userRepo, $tokenRepo);

        $this->expectException(\RuntimeException::class);

        $validator->validate('wrong_field', 'value');
    }

    public function test_user_owns_token_returns_false_when_user_not_found(): void
    {
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('input')->with('email', null)->andReturn('foo@bar.com');

        $userRepo = Mockery::mock(UserRepositoryContract::class);
        $userRepo->shouldReceive('findByEmail')->once()->with('foo@bar.com')->andReturn(null);

        $tokenRepo = Mockery::mock(PasswordTokenRepositoryContract::class);
        $tokenRepo->shouldNotReceive('findForUser');

        $validator = new UserOwnsTokenValidator($request, $userRepo, $tokenRepo);

        $this->assertFalse($validator->validate('token', 'value'));
    }

    public function test_token_is_not_expired_returns_false_when_user_not_found(): void
    {
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('input')->with('email', null)->andReturn('foo@bar.com');

        $userRepo = Mockery::mock(UserRepositoryContract::class);
        $userRepo->shouldReceive('findByEmail')->once()->with('foo@bar.com')->andReturn(null);

        $tokenRepo = Mockery::mock(PasswordTokenRepositoryContract::class);
        $tokenRepo->shouldNotReceive('findForUser');

        $validator = new TokenIsNotExpiredValidator($request, $userRepo, $tokenRepo);

        $this->assertFalse($validator->validate('token', 'value'));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
