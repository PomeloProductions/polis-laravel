<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Validators;

use App\Models\User\InvitationToken;
use Polis\Contracts\Repositories\User\InvitationTokenRepositoryContract;
use Polis\Tests\CustomMockInterface;
use Polis\Tests\TestCase;
use Polis\Validators\InvitationTokenIsValidValidator;

/**
 * Class InvitationTokenIsValidValidatorTest
 */
final class InvitationTokenIsValidValidatorTest extends TestCase
{
    public function test_validate_returns_true_with_valid_unused_token(): void
    {
        /** @var InvitationTokenRepositoryContract|CustomMockInterface $repository */
        $repository = mock(InvitationTokenRepositoryContract::class);

        $invitationToken = new InvitationToken;
        $invitationToken->token = 'valid-token';
        $invitationToken->used_at = null;

        $repository->shouldReceive('findByToken')
            ->once()
            ->with('valid-token')
            ->andReturn($invitationToken);

        $validator = new InvitationTokenIsValidValidator($repository);

        $this->assertTrue($validator->validate('invitation_token', 'valid-token'));
    }

    public function test_validate_returns_false_when_token_not_found(): void
    {
        /** @var InvitationTokenRepositoryContract|CustomMockInterface $repository */
        $repository = mock(InvitationTokenRepositoryContract::class);

        $repository->shouldReceive('findByToken')
            ->once()
            ->with('invalid-token')
            ->andReturn(null);

        $validator = new InvitationTokenIsValidValidator($repository);

        $this->assertFalse($validator->validate('invitation_token', 'invalid-token'));
    }

    public function test_validate_returns_false_when_token_already_used(): void
    {
        /** @var InvitationTokenRepositoryContract|CustomMockInterface $repository */
        $repository = mock(InvitationTokenRepositoryContract::class);

        $invitationToken = new InvitationToken;
        $invitationToken->token = 'used-token';
        $invitationToken->used_at = now();

        $repository->shouldReceive('findByToken')
            ->once()
            ->with('used-token')
            ->andReturn($invitationToken);

        $validator = new InvitationTokenIsValidValidator($repository);

        $this->assertFalse($validator->validate('invitation_token', 'used-token'));
    }

    public function test_validate_returns_false_when_value_is_not_string(): void
    {
        /** @var InvitationTokenRepositoryContract|CustomMockInterface $repository */
        $repository = mock(InvitationTokenRepositoryContract::class);

        $validator = new InvitationTokenIsValidValidator($repository);

        $this->assertFalse($validator->validate('invitation_token', 123));
    }

    public function test_validate_returns_false_when_value_is_array(): void
    {
        /** @var InvitationTokenRepositoryContract|CustomMockInterface $repository */
        $repository = mock(InvitationTokenRepositoryContract::class);

        $validator = new InvitationTokenIsValidValidator($repository);

        $this->assertFalse($validator->validate('invitation_token', ['invalid']));
    }
}
