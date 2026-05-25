<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Listeners\User;

use App\Models\Role;
use App\Models\User\InvitationToken;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;
use Polis\Contracts\Repositories\User\InvitationTokenRepositoryContract;
use Polis\Events\User\InvitationAcceptedEvent;
use Polis\Listeners\User\InvitationAcceptedListener;
use Polis\Tests\CustomMockInterface;
use Polis\Tests\TestCase;

/**
 * Class InvitationAcceptedListenerTest
 */
final class InvitationAcceptedListenerTest extends TestCase
{
    public function test_handle_with_role(): void
    {
        /** @var InvitationTokenRepositoryContract|CustomMockInterface $repository */
        $repository = mock(InvitationTokenRepositoryContract::class);

        $listener = new InvitationAcceptedListener($repository);

        $user = new User;
        $user->id = 1;

        $invitationToken = new InvitationToken;
        $invitationToken->id = 1;
        $invitationToken->token = 'test-token';
        $invitationToken->role_id = Role::ARTICLE_EDITOR;

        $event = new InvitationAcceptedEvent($user, $invitationToken);

        // Mock the repository update call
        $repository->shouldReceive('update')->once()->with($invitationToken, \Mockery::on(function ($data) {
            $this->assertArrayHasKey('used_at', $data);
            $this->assertInstanceOf(Carbon::class, $data['used_at']);

            return true;
        }));

        // Mock the roles relationship
        $rolesRelation = mock(BelongsToMany::class);
        $rolesRelation->shouldReceive('attach')->once()->with(Role::ARTICLE_EDITOR);
        $user->setRelation('roles', $rolesRelation);

        // We need to mock the roles() method
        $user = \Mockery::mock($user)->makePartial();
        $user->shouldReceive('roles')->once()->andReturn($rolesRelation);

        // Update the event with the mocked user
        $event = new InvitationAcceptedEvent($user, $invitationToken);

        $listener->handle($event);
    }

    public function test_handle_without_role(): void
    {
        /** @var InvitationTokenRepositoryContract|CustomMockInterface $repository */
        $repository = mock(InvitationTokenRepositoryContract::class);

        $listener = new InvitationAcceptedListener($repository);

        $user = new User;
        $user->id = 1;

        $invitationToken = new InvitationToken;
        $invitationToken->id = 1;
        $invitationToken->token = 'test-token';
        $invitationToken->role_id = null;

        $event = new InvitationAcceptedEvent($user, $invitationToken);

        // Mock the repository update call
        $repository->shouldReceive('update')->once()->with($invitationToken, \Mockery::on(function ($data) {
            $this->assertArrayHasKey('used_at', $data);
            $this->assertInstanceOf(Carbon::class, $data['used_at']);

            return true;
        }));

        // Mock the roles relationship - it should NOT be called
        $rolesRelation = mock(BelongsToMany::class);
        $rolesRelation->shouldReceive('attach')->never();

        $user = \Mockery::mock($user)->makePartial();
        $user->shouldReceive('roles')->never();

        // Update the event with the mocked user
        $event = new InvitationAcceptedEvent($user, $invitationToken);

        $listener->handle($event);
    }
}
