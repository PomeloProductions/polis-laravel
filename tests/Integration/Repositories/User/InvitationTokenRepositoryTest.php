<?php

declare(strict_types=1);

namespace Polis\Tests\Integration\Repositories\User;

use App\Models\Role;
use App\Models\User\InvitationToken;
use Polis\Contracts\Services\TokenGenerationServiceContract;
use Polis\Repositories\User\InvitationTokenRepository;
use Polis\Tests\CustomMockInterface;
use Polis\Tests\Application\ApplicationTestCase;

/**
 * Class InvitationTokenRepositoryTest
 */
final class InvitationTokenRepositoryTest extends ApplicationTestCase
{
    
    /**
     * @var TokenGenerationServiceContract|CustomMockInterface
     */
    private $tokenGenerationService;

    /**
     * @var InvitationTokenRepository
     */
    private $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();

        $this->tokenGenerationService = mock(TokenGenerationServiceContract::class);
        $this->repository = new InvitationTokenRepository(
            new InvitationToken,
            $this->getGenericLogMock(),
            $this->tokenGenerationService
        );
    }

    public function test_find_all_success(): void
    {
        InvitationToken::factory()->count(3)->create();

        $items = $this->repository->findAll();
        $this->assertCount(3, $items);
    }

    public function test_find_or_fail_success(): void
    {
        $token = InvitationToken::factory()->create();

        $found = $this->repository->findOrFail($token->id);
        $this->assertEquals($token->id, $found->id);
    }

    public function test_delete_success(): void
    {
        $token = InvitationToken::factory()->create();

        $this->repository->delete($token);

        $this->assertSoftDeleted('invitation_tokens', ['id' => $token->id]);
    }

    public function test_create_success(): void
    {
        $role = Role::find(Role::ARTICLE_EDITOR);

        /** @var InvitationToken $invitationToken */
        $invitationToken = $this->repository->create([
            'token' => 'hello-world',
            'role_id' => $role->id,
        ]);

        $this->assertEquals('hello-world', $invitationToken->token);
        $this->assertEquals($role->id, $invitationToken->role_id);
        $this->assertNull($invitationToken->used_at);
    }

    public function test_create_success_without_role(): void
    {
        /** @var InvitationToken $invitationToken */
        $invitationToken = $this->repository->create([
            'token' => 'hello-world',
        ]);

        $this->assertEquals('hello-world', $invitationToken->token);
        $this->assertNull($invitationToken->role_id);
        $this->assertNull($invitationToken->used_at);
    }

    public function test_update_success(): void
    {
        $invitationToken = InvitationToken::factory()->create([
            'token' => 'test-token',
            'used_at' => null,
        ]);

        $this->assertNull($invitationToken->used_at);

        $this->repository->update($invitationToken, [
            'used_at' => now(),
        ]);

        $invitationToken->refresh();
        $this->assertNotNull($invitationToken->used_at);
    }

    public function test_find_by_token(): void
    {
        $invitationToken = InvitationToken::factory()->create([
            'token' => '1234',
        ]);

        $this->assertEquals($invitationToken->id, $this->repository->findByToken('1234')->id);
        $this->assertNull($this->repository->findByToken('12345'));
    }

    public function test_generate_unique_token_success(): void
    {
        $this->tokenGenerationService->shouldReceive('generateToken')->once()->andReturn('unique-token-12345');

        $this->assertEquals('unique-token-12345', $this->repository->generateUniqueToken());
    }

    public function test_generate_unique_token_throws_exception(): void
    {
        InvitationToken::factory()->create([
            'token' => '12345',
        ]);

        $this->tokenGenerationService->shouldReceive('generateToken')->times(5)->andReturn('12345');
        $this->expectException(\OverflowException::class);

        $this->repository->generateUniqueToken();
    }
}
