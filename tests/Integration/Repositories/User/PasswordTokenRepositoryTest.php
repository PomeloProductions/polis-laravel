<?php

declare(strict_types=1);

namespace Polis\Tests\Integration\Repositories\User;

use App\Models\User\PasswordToken;
use App\Models\User\User;
use Illuminate\Contracts\Events\Dispatcher;
use Polis\Contracts\Services\TokenGenerationServiceContract;
use Polis\Events\User\ForgotPasswordEvent;
use Polis\Exceptions\NotImplementedException;
use Polis\Repositories\User\PasswordTokenRepository;
use Polis\Tests\CustomMockInterface;
use Polis\Tests\Application\ApplicationTestCase;

/**
 * Class PasswordTokenRepositoryTest
 */
final class PasswordTokenRepositoryTest extends ApplicationTestCase
{
    
    /**
     * @var Dispatcher|CustomMockInterface
     */
    private $dispatcher;

    /**
     * @var TokenGenerationServiceContract|CustomMockInterface
     */
    private $tokenGenerationService;

    /**
     * @var PasswordTokenRepository
     */
    private $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();

        $this->dispatcher = mock(Dispatcher::class);
        $this->tokenGenerationService = mock(TokenGenerationServiceContract::class);
        $this->repository = new PasswordTokenRepository(
            new PasswordToken,
            $this->getGenericLogMock(),
            $this->dispatcher,
            $this->tokenGenerationService
        );
    }

    public function test_find_all_throws_exception(): void
    {
        $this->expectException(NotImplementedException::class);

        $this->repository->findAll();
    }

    public function test_find_or_fail_throws_exception(): void
    {
        $this->expectException(NotImplementedException::class);

        $this->repository->findOrFail(1);
    }

    public function test_delete_throws_exception(): void
    {
        $this->expectException(NotImplementedException::class);

        $this->repository->delete(new PasswordToken);
    }

    public function test_update_throws_exception(): void
    {
        $this->expectException(NotImplementedException::class);

        $this->repository->update(new PasswordToken, []);
    }

    public function test_create_success(): void
    {
        $this->dispatcher->shouldReceive('dispatch')->once()
            ->with(\Mockery::on(function (ForgotPasswordEvent $event) {
                return true;
            })
            );

        $user = User::factory()->create();

        /** @var PasswordToken $passwordToken */
        $passwordToken = $this->repository->create([
            'token' => 'hello',
        ], $user);

        $this->assertEquals('hello', $passwordToken->token);
        $this->assertEquals($user->id, $passwordToken->user_id);
    }

    public function test_find_for_user(): void
    {
        $user = User::factory()->create();
        $passwordToken = PasswordToken::factory()->create([
            'token' => '1234',
            'user_id' => $user->id,
        ]);

        $this->assertEquals($passwordToken->id, $this->repository->findForUser($user, '1234')->id);
        $this->assertNull($this->repository->findForUser($user, '12345'));
    }

    public function test_generate_unique_token_success(): void
    {
        $user = User::factory()->create();

        $this->tokenGenerationService->shouldReceive('generateToken')->once()->andReturn('12345');

        $this->assertEquals('12345', $this->repository->generateUniqueToken($user));
    }

    public function test_generate_unique_token_throws_exception(): void
    {
        $user = User::factory()->create();
        PasswordToken::factory()->create([
            'user_id' => $user->id,
            'token' => '12345',
        ]);

        $this->tokenGenerationService->shouldReceive('generateToken')->times(5)->andReturn('12345');
        $this->expectException(\OverflowException::class);

        $this->repository->generateUniqueToken($user);
    }
}
