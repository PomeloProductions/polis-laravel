<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Policies;

use Mockery;
use Polis\Models\User\ExternalAccountConnection;
use Polis\Policies\User\ExternalAccountConnectionPolicyAbstract;
use Polis\Tests\Fixtures\Policies\User\ExternalAccountConnectionPolicy;
use Polis\Tests\TestCase;

/**
 * Owner-only gate coverage for
 * {@see ExternalAccountConnectionPolicyAbstract}.
 *
 * The contract under test: only the user who owns a connection (i.e. the
 * row's user_id == loggedInUser->id == requestedUser->id) may
 * list / view / create / update / delete their own connections. Tested
 * exhaustively here so that future refactors of the policy must
 * deliberately revisit every gate.
 */
final class ExternalAccountConnectionPolicyAbstractTest extends TestCase
{
    public function test_all_allows_self(): void
    {
        $policy = new ExternalAccountConnectionPolicy;
        $user = Mockery::mock('App\\Models\\User\\User');
        $user->id = 1;

        $this->assertTrue($policy->all($user, $user));
    }

    public function test_all_denies_other(): void
    {
        $policy = new ExternalAccountConnectionPolicy;
        $loggedIn = Mockery::mock('App\\Models\\User\\User');
        $loggedIn->id = 1;
        $requested = Mockery::mock('App\\Models\\User\\User');
        $requested->id = 2;

        $this->assertFalse($policy->all($loggedIn, $requested));
    }

    public function test_view_allows_self_with_owned_connection(): void
    {
        $policy = new ExternalAccountConnectionPolicy;
        $user = Mockery::mock('App\\Models\\User\\User');
        $user->id = 7;

        $connection = new ExternalAccountConnection;
        $connection->user_id = 7;

        $this->assertTrue($policy->view($user, $user, $connection));
    }

    public function test_view_denies_when_connection_belongs_to_other_user(): void
    {
        $policy = new ExternalAccountConnectionPolicy;
        $user = Mockery::mock('App\\Models\\User\\User');
        $user->id = 7;

        $connection = new ExternalAccountConnection;
        $connection->user_id = 999;

        $this->assertFalse($policy->view($user, $user, $connection));
    }

    public function test_view_denies_when_logged_in_user_is_different_from_requested_user(): void
    {
        $policy = new ExternalAccountConnectionPolicy;
        $loggedIn = Mockery::mock('App\\Models\\User\\User');
        $loggedIn->id = 7;
        $requested = Mockery::mock('App\\Models\\User\\User');
        $requested->id = 8;

        $connection = new ExternalAccountConnection;
        $connection->user_id = 8; // belongs to the requested user

        // Even though the connection belongs to the requested user, the
        // logged-in user is impersonating them — still denied.
        $this->assertFalse($policy->view($loggedIn, $requested, $connection));
    }

    public function test_create_allows_self(): void
    {
        $policy = new ExternalAccountConnectionPolicy;
        $user = Mockery::mock('App\\Models\\User\\User');
        $user->id = 7;

        $this->assertTrue($policy->create($user, $user));
    }

    public function test_create_denies_other(): void
    {
        $policy = new ExternalAccountConnectionPolicy;
        $loggedIn = Mockery::mock('App\\Models\\User\\User');
        $loggedIn->id = 7;
        $requested = Mockery::mock('App\\Models\\User\\User');
        $requested->id = 8;

        $this->assertFalse($policy->create($loggedIn, $requested));
    }

    public function test_update_allows_self_with_owned_connection(): void
    {
        $policy = new ExternalAccountConnectionPolicy;
        $user = Mockery::mock('App\\Models\\User\\User');
        $user->id = 7;
        $connection = new ExternalAccountConnection;
        $connection->user_id = 7;

        $this->assertTrue($policy->update($user, $user, $connection));
    }

    public function test_update_denies_when_connection_belongs_to_someone_else(): void
    {
        $policy = new ExternalAccountConnectionPolicy;
        $user = Mockery::mock('App\\Models\\User\\User');
        $user->id = 7;
        $connection = new ExternalAccountConnection;
        $connection->user_id = 8;

        $this->assertFalse($policy->update($user, $user, $connection));
    }

    public function test_delete_allows_self_with_owned_connection(): void
    {
        $policy = new ExternalAccountConnectionPolicy;
        $user = Mockery::mock('App\\Models\\User\\User');
        $user->id = 7;
        $connection = new ExternalAccountConnection;
        $connection->user_id = 7;

        $this->assertTrue($policy->delete($user, $user, $connection));
    }

    public function test_delete_denies_when_connection_belongs_to_someone_else(): void
    {
        $policy = new ExternalAccountConnectionPolicy;
        $user = Mockery::mock('App\\Models\\User\\User');
        $user->id = 7;
        $connection = new ExternalAccountConnection;
        $connection->user_id = 99;

        $this->assertFalse($policy->delete($user, $user, $connection));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
