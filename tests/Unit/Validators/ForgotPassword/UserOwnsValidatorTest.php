<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Validators\ForgotPassword;

use App\Models\User\PasswordToken;
use App\Models\User\User;
use Illuminate\Http\Request;
use Polis\Contracts\Repositories\User\PasswordTokenRepositoryContract;
use Polis\Contracts\Repositories\User\UserRepositoryContract;
use Polis\Tests\CustomMockInterface;
use Polis\Tests\TestCase;
use Polis\Validators\ForgotPassword\UserOwnsTokenValidator;

/**
 * Class UserOwnsValidatorTest
 */
final class UserOwnsValidatorTest extends TestCase
{
    /**
     * @var Request|CustomMockInterface
     */
    private $request;

    /**
     * @var UserRepositoryContract|CustomMockInterface
     */
    private $userRepository;

    /**
     * @var PasswordTokenRepositoryContract|CustomMockInterface
     */
    private $passwordTokenRepository;

    /**
     * @var UserOwnsTokenValidator
     */
    private $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->request = mock(Request::class);
        $this->userRepository = mock(UserRepositoryContract::class);
        $this->passwordTokenRepository = mock(PasswordTokenRepositoryContract::class);

        $this->validator = new UserOwnsTokenValidator(
            $this->request,
            $this->userRepository,
            $this->passwordTokenRepository
        );
    }

    public function test_fails_no_email_in_request(): void
    {
        $this->request->shouldReceive('input')->once()->with('email', null)->andReturn(null);

        $this->assertFalse($this->validator->validate('token', 'hello'));
    }

    public function test_fails_user_not_found(): void
    {
        $this->request->shouldReceive('input')->once()->with('email', null)->andReturn('test@test.com');

        $this->userRepository->shouldReceive('findByEmail')->with('test@test.com')->andReturn(null);

        $this->assertFalse($this->validator->validate('token', 'hello'));
    }

    public function test_fails_token_not_found(): void
    {
        $user = new User;

        $this->request->shouldReceive('input')->once()->with('email', null)->andReturn('test@test.com');

        $this->userRepository->shouldReceive('findByEmail')->once()->with('test@test.com')->andReturn($user);

        $this->passwordTokenRepository->shouldReceive('findForUser')->once()
            ->with($user, 'hello')->andReturn(null);

        $this->assertFalse($this->validator->validate('token', 'hello'));
    }

    public function test_passes(): void
    {
        $user = new User;
        $passwordToken = new PasswordToken;

        $this->request->shouldReceive('input')->once()->with('email', null)->andReturn('test@test.com');

        $this->userRepository->shouldReceive('findByEmail')->once()->with('test@test.com')->andReturn($user);

        $this->passwordTokenRepository->shouldReceive('findForUser')->once()
            ->with($user, 'hello')->andReturn($passwordToken);

        $this->assertTrue($this->validator->validate('token', 'hello'));
    }
}
