<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Validators;

use Mockery;
use Polis\Contracts\Repositories\User\InvitationTokenRepositoryContract;
use Polis\Tests\TestCase;
use Polis\Validators\InvitationTokenIsValidValidator;

/**
 * Standalone-runnable coverage for InvitationTokenIsValidValidator. The
 * existing test under tests/Unit/Validators/InvitationTokenIsValidValidatorTest.php
 * imports App\Models\User\InvitationToken (consumer-app) — that test
 * lives in the Consumer-Only suite. This test only covers the
 * not-string short-circuit branch, which is the only validator path
 * that does not require an InvitationToken instance (the return-type of
 * findByToken is the Polis\Models\User\InvitationToken Eloquent model
 * which in turn pulls in the AdminUI EloquentJoin trait that lives in
 * the consumer-app — preventing standalone instantiation/mocking here).
 */
final class InvitationTokenIsValidValidatorStandaloneTest extends TestCase
{
    public function test_returns_false_when_value_is_not_a_string(): void
    {
        $repo = Mockery::mock(InvitationTokenRepositoryContract::class);
        $repo->shouldNotReceive('findByToken');

        $validator = new InvitationTokenIsValidValidator($repo);

        $this->assertFalse($validator->validate('invitation_token', 123));
        $this->assertFalse($validator->validate('invitation_token', ['foo']));
        $this->assertFalse($validator->validate('invitation_token', null));
        $this->assertFalse($validator->validate('invitation_token', false));
        $this->assertFalse($validator->validate('invitation_token', new \stdClass));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
