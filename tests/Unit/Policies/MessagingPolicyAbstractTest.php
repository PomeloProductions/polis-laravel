<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Policies;

use Mockery;
use Polis\Contracts\ThreadSecurity\ThreadSubjectGateContract;
use Polis\Contracts\ThreadSecurity\ThreadSubjectGateProviderContract;
use Polis\Tests\Fixtures\Models\Message;
use Polis\Tests\Fixtures\Models\Thread;
use Polis\Tests\Fixtures\Policies\Messaging\MessagePolicy;
use Polis\Tests\Fixtures\Policies\Messaging\ThreadPolicy;
use Polis\Tests\TestCase;

/**
 * Coverage for the messaging policy abstracts.
 *
 * Both abstracts route the subject-type to a ThreadSubjectGate via a
 * ThreadSubjectGateProvider. A null gate (subject_type unknown) returns
 * false; otherwise the user-id match + gate authorize result decides.
 * MessagePolicy::update additionally validates that the message belongs
 * to the named thread and is addressed to the logged-in user.
 */
final class MessagingPolicyAbstractTest extends TestCase
{
    public function test_thread_policy_all_returns_false_when_provider_yields_no_gate(): void
    {
        $provider = Mockery::mock(ThreadSubjectGateProviderContract::class);
        $provider->shouldReceive('createGate')->once()->with('unknown.subject')->andReturn(null);
        $policy = new ThreadPolicy($provider);

        $user = Mockery::mock('App\\Models\\User\\User');
        $this->assertFalse($policy->all($user, $user, 'unknown.subject', null));
    }

    public function test_thread_policy_all_allows_when_gate_authorizes(): void
    {
        $gate = Mockery::mock(ThreadSubjectGateContract::class);
        $gate->shouldReceive('authorizeSubject')->once()->andReturn(true);

        $provider = Mockery::mock(ThreadSubjectGateProviderContract::class);
        $provider->shouldReceive('createGate')->once()->with('article')->andReturn($gate);

        $policy = new ThreadPolicy($provider);
        $user = Mockery::mock('App\\Models\\User\\User');
        $user->id = 1;

        $this->assertTrue($policy->all($user, $user, 'article', 5));
    }

    public function test_thread_policy_all_denies_cross_user(): void
    {
        $gate = Mockery::mock(ThreadSubjectGateContract::class);
        // authorizeSubject may not be called due to short-circuit; declare no expectation.

        $provider = Mockery::mock(ThreadSubjectGateProviderContract::class);
        $provider->shouldReceive('createGate')->once()->with('article')->andReturn($gate);

        $policy = new ThreadPolicy($provider);
        $loggedIn = Mockery::mock('App\\Models\\User\\User');
        $loggedIn->id = 1;
        $requested = Mockery::mock('App\\Models\\User\\User');
        $requested->id = 2;

        $this->assertFalse($policy->all($loggedIn, $requested, 'article'));
    }

    public function test_thread_policy_create_returns_false_when_no_gate(): void
    {
        $provider = Mockery::mock(ThreadSubjectGateProviderContract::class);
        $provider->shouldReceive('createGate')->once()->with('unknown')->andReturn(null);

        $policy = new ThreadPolicy($provider);
        $user = Mockery::mock('App\\Models\\User\\User');

        $this->assertFalse($policy->create($user, $user, 'unknown'));
    }

    public function test_thread_policy_create_allows_when_gate_authorizes(): void
    {
        $gate = Mockery::mock(ThreadSubjectGateContract::class);
        $gate->shouldReceive('authorizeSubject')->once()->andReturn(true);

        $provider = Mockery::mock(ThreadSubjectGateProviderContract::class);
        $provider->shouldReceive('createGate')->once()->with('article')->andReturn($gate);

        $policy = new ThreadPolicy($provider);
        $user = Mockery::mock('App\\Models\\User\\User');
        $user->id = 1;

        $this->assertTrue($policy->create($user, $user, 'article', 5));
    }

    public function test_message_policy_all_returns_false_when_no_gate(): void
    {
        $provider = Mockery::mock(ThreadSubjectGateProviderContract::class);
        $provider->shouldReceive('createGate')->once()->andReturn(null);

        $policy = new MessagePolicy($provider);
        $user = Mockery::mock('App\\Models\\User\\User');
        $thread = new Thread;
        $thread->subject_type = 'unknown';

        $this->assertFalse($policy->all($user, $user, $thread));
    }

    public function test_message_policy_all_allows_when_gate_authorizes_thread(): void
    {
        $gate = Mockery::mock(ThreadSubjectGateContract::class);
        $gate->shouldReceive('authorizeThread')->once()->andReturn(true);

        $provider = Mockery::mock(ThreadSubjectGateProviderContract::class);
        $provider->shouldReceive('createGate')->once()->andReturn($gate);

        $policy = new MessagePolicy($provider);
        $user = Mockery::mock('App\\Models\\User\\User');
        $user->id = 1;
        $thread = new Thread;
        $thread->subject_type = 'article';

        $this->assertTrue($policy->all($user, $user, $thread));
    }

    public function test_message_policy_create_denies_cross_user(): void
    {
        $gate = Mockery::mock(ThreadSubjectGateContract::class);

        $provider = Mockery::mock(ThreadSubjectGateProviderContract::class);
        $provider->shouldReceive('createGate')->once()->andReturn($gate);

        $policy = new MessagePolicy($provider);
        $loggedIn = Mockery::mock('App\\Models\\User\\User');
        $loggedIn->id = 1;
        $requested = Mockery::mock('App\\Models\\User\\User');
        $requested->id = 2;
        $thread = new Thread;
        $thread->subject_type = 'article';

        $this->assertFalse($policy->create($loggedIn, $requested, $thread));
    }

    public function test_message_policy_create_returns_false_when_no_gate(): void
    {
        $provider = Mockery::mock(ThreadSubjectGateProviderContract::class);
        $provider->shouldReceive('createGate')->once()->andReturn(null);

        $policy = new MessagePolicy($provider);
        $user = Mockery::mock('App\\Models\\User\\User');
        $thread = new Thread;
        $thread->subject_type = 'x';

        $this->assertFalse($policy->create($user, $user, $thread));
    }

    public function test_message_policy_update_allows_when_message_belongs_to_thread_and_addressed_to_user(): void
    {
        $gate = Mockery::mock(ThreadSubjectGateContract::class);
        $gate->shouldReceive('authorizeThread')->once()->andReturn(true);

        $provider = Mockery::mock(ThreadSubjectGateProviderContract::class);
        $provider->shouldReceive('createGate')->once()->andReturn($gate);

        $policy = new MessagePolicy($provider);
        $user = Mockery::mock('App\\Models\\User\\User');
        $user->id = 1;
        $thread = new Thread;
        $thread->id = 7;
        $thread->subject_type = 'article';
        $message = new Message;
        $message->thread_id = 7;
        $message->to_id = 1;

        $this->assertTrue($policy->update($user, $user, $thread, $message));
    }

    public function test_message_policy_update_denies_when_message_belongs_to_other_thread(): void
    {
        $gate = Mockery::mock(ThreadSubjectGateContract::class);
        $gate->shouldReceive('authorizeThread')->once()->andReturn(true);

        $provider = Mockery::mock(ThreadSubjectGateProviderContract::class);
        $provider->shouldReceive('createGate')->once()->andReturn($gate);

        $policy = new MessagePolicy($provider);
        $user = Mockery::mock('App\\Models\\User\\User');
        $user->id = 1;
        $thread = new Thread;
        $thread->id = 7;
        $thread->subject_type = 'article';
        $message = new Message;
        $message->thread_id = 99; // mismatch
        $message->to_id = 1;

        $this->assertFalse($policy->update($user, $user, $thread, $message));
    }

    public function test_message_policy_update_denies_when_message_addressed_to_other_user(): void
    {
        $gate = Mockery::mock(ThreadSubjectGateContract::class);
        $gate->shouldReceive('authorizeThread')->once()->andReturn(true);

        $provider = Mockery::mock(ThreadSubjectGateProviderContract::class);
        $provider->shouldReceive('createGate')->once()->andReturn($gate);

        $policy = new MessagePolicy($provider);
        $user = Mockery::mock('App\\Models\\User\\User');
        $user->id = 1;
        $thread = new Thread;
        $thread->id = 7;
        $thread->subject_type = 'article';
        $message = new Message;
        $message->thread_id = 7;
        $message->to_id = 99;

        $this->assertFalse($policy->update($user, $user, $thread, $message));
    }

    public function test_message_policy_update_returns_false_when_no_gate(): void
    {
        $provider = Mockery::mock(ThreadSubjectGateProviderContract::class);
        $provider->shouldReceive('createGate')->once()->andReturn(null);

        $policy = new MessagePolicy($provider);
        $user = Mockery::mock('App\\Models\\User\\User');
        $thread = new Thread;
        $thread->subject_type = 'x';
        $message = new Message;

        $this->assertFalse($policy->update($user, $user, $thread, $message));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
