<?php

declare(strict_types=1);

namespace Polis\Tests\Integration\Policies\Messaging;

use App\Models\Messaging\Message;
use App\Models\Messaging\Thread;
use App\Models\User\User;
use App\Policies\Messaging\MessagePolicy;
use Polis\Contracts\ThreadSecurity\ThreadSubjectGateContract;
use Polis\Contracts\ThreadSecurity\ThreadSubjectGateProviderContract;
use Polis\Tests\CustomMockInterface;
use Polis\Tests\Application\ApplicationTestCase;

/**
 * Class MessagePolicyTest
 */
final class MessagePolicyTest extends ApplicationTestCase
{
    
    /**
     * @var ThreadSubjectGateProviderContract|CustomMockInterface
     */
    private $gateProvider;

    /**
     * @var MessagePolicy
     */
    private $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();
        $this->gateProvider = mock(ThreadSubjectGateProviderContract::class);
        $this->policy = new MessagePolicy($this->gateProvider);
    }

    public function test_all_blocks_when_gate_not_found(): void
    {
        $loggedInUser = User::factory()->create();
        $requestedUser = User::factory()->create();

        $thread = Thread::factory()->create([
            'subject_type' => 'a_type',
        ]);

        $this->gateProvider->shouldReceive('createGate')->once()->with('a_type')->andReturnNull();

        $this->assertFalse($this->policy->all($loggedInUser, $requestedUser, $thread));
    }

    public function test_all_block_when_accessing_another_user(): void
    {
        $loggedInUser = User::factory()->create();
        $requestedUser = User::factory()->create();

        $thread = Thread::factory()->create([
            'subject_type' => 'a_type',
        ]);

        $gate = mock(ThreadSubjectGateContract::class);

        $this->gateProvider->shouldReceive('createGate')->once()->with('a_type')->andReturn($gate);

        $this->assertFalse($this->policy->all($loggedInUser, $requestedUser, $thread));
    }

    public function test_all_block_when_gate_fails(): void
    {
        $loggedInUser = User::factory()->create();

        $thread = Thread::factory()->create([
            'subject_type' => 'a_type',
        ]);

        $gate = mock(ThreadSubjectGateContract::class);

        $this->gateProvider->shouldReceive('createGate')->once()->with('a_type')->andReturn($gate);
        $gate->shouldReceive('authorizeThread')->once()->with($loggedInUser, $thread)->andReturnFalse();

        $this->assertFalse($this->policy->all($loggedInUser, $loggedInUser, $thread));
    }

    public function test_all_passes(): void
    {
        $loggedInUser = User::factory()->create();

        $thread = Thread::factory()->create([
            'subject_type' => 'a_type',
        ]);

        $gate = mock(ThreadSubjectGateContract::class);

        $this->gateProvider->shouldReceive('createGate')->once()->with('a_type')->andReturn($gate);
        $gate->shouldReceive('authorizeThread')->once()->with($loggedInUser, $thread)->andReturnTrue();

        $this->assertTrue($this->policy->all($loggedInUser, $loggedInUser, $thread));
    }

    public function test_create_blocks_when_gate_not_found(): void
    {
        $loggedInUser = User::factory()->create();
        $requestedUser = User::factory()->create();

        $thread = Thread::factory()->create([
            'subject_type' => 'a_type',
        ]);

        $this->gateProvider->shouldReceive('createGate')->once()->with('a_type')->andReturnNull();

        $this->assertFalse($this->policy->create($loggedInUser, $requestedUser, $thread));
    }

    public function test_create_block_when_accessing_another_user(): void
    {
        $loggedInUser = User::factory()->create();
        $requestedUser = User::factory()->create();

        $thread = Thread::factory()->create([
            'subject_type' => 'a_type',
        ]);

        $gate = mock(ThreadSubjectGateContract::class);

        $this->gateProvider->shouldReceive('createGate')->once()->with('a_type')->andReturn($gate);

        $this->assertFalse($this->policy->create($loggedInUser, $requestedUser, $thread));
    }

    public function test_create_block_when_gate_fails(): void
    {
        $loggedInUser = User::factory()->create();

        $thread = Thread::factory()->create([
            'subject_type' => 'a_type',
        ]);

        $gate = mock(ThreadSubjectGateContract::class);

        $this->gateProvider->shouldReceive('createGate')->once()->with('a_type')->andReturn($gate);
        $gate->shouldReceive('authorizeThread')->once()->with($loggedInUser, $thread)->andReturnFalse();

        $this->assertFalse($this->policy->create($loggedInUser, $loggedInUser, $thread));
    }

    public function test_create_passes(): void
    {
        $loggedInUser = User::factory()->create();

        $thread = Thread::factory()->create([
            'subject_type' => 'a_type',
        ]);

        $gate = mock(ThreadSubjectGateContract::class);

        $this->gateProvider->shouldReceive('createGate')->once()->with('a_type')->andReturn($gate);
        $gate->shouldReceive('authorizeThread')->once()->with($loggedInUser, $thread)->andReturnTrue();

        $this->assertTrue($this->policy->create($loggedInUser, $loggedInUser, $thread));
    }

    public function test_update_blocks_when_gate_not_found(): void
    {
        $loggedInUser = User::factory()->create();
        $requestedUser = User::factory()->create();

        $thread = Thread::factory()->create([
            'subject_type' => 'a_type',
        ]);
        $message = Message::factory()->create();

        $this->gateProvider->shouldReceive('createGate')->once()->with('a_type')->andReturnNull();

        $this->assertFalse($this->policy->update($loggedInUser, $requestedUser, $thread, $message));
    }

    public function test_update_blocks_user_mismatch(): void
    {
        $loggedInUser = User::factory()->create();
        $requestedUser = User::factory()->create();
        $thread = Thread::factory()->create([
            'subject_type' => 'a_type',
        ]);
        $message = Message::factory()->create();

        $gate = mock(ThreadSubjectGateContract::class);

        $this->gateProvider->shouldReceive('createGate')->once()->with('a_type')->andReturn($gate);

        $this->assertFalse($this->policy->update($loggedInUser, $requestedUser, $thread, $message));
    }

    public function test_update_block_when_gate_fails(): void
    {
        $loggedInUser = User::factory()->create();

        $thread = Thread::factory()->create([
            'subject_type' => 'a_type',
        ]);
        $message = Message::factory()->create();

        $gate = mock(ThreadSubjectGateContract::class);

        $this->gateProvider->shouldReceive('createGate')->once()->with('a_type')->andReturn($gate);
        $gate->shouldReceive('authorizeThread')->once()->with($loggedInUser, $thread)->andReturnFalse();

        $this->assertFalse($this->policy->update($loggedInUser, $loggedInUser, $thread, $message));
    }

    public function test_update_blocks_message_not_in_thread(): void
    {
        $thread = Thread::factory()->create([
            'subject_type' => 'a_type',
        ]);
        $user = User::factory()->create();
        $message = Message::factory()->create();

        $gate = mock(ThreadSubjectGateContract::class);

        $this->gateProvider->shouldReceive('createGate')->once()->with('a_type')->andReturn($gate);
        $gate->shouldReceive('authorizeThread')->once()->with($user, $thread)->andReturnTrue();

        $this->assertFalse($this->policy->update($user, $user, $thread, $message));
    }

    public function test_update_blocks_user_not_sent_message(): void
    {
        $thread = Thread::factory()->create([
            'subject_type' => 'a_type',
        ]);
        $user = User::factory()->create();
        $message = Message::factory()->create([
            'thread_id' => $thread->id,
            'to_id' => User::factory()->create()->id,
        ]);

        $gate = mock(ThreadSubjectGateContract::class);

        $this->gateProvider->shouldReceive('createGate')->once()->with('a_type')->andReturn($gate);
        $gate->shouldReceive('authorizeThread')->once()->with($user, $thread)->andReturnTrue();

        $this->assertFalse($this->policy->update($user, $user, $thread, $message));
    }

    public function test_update_passes(): void
    {
        $thread = Thread::factory()->create([
            'subject_type' => 'a_type',
        ]);
        $user = User::factory()->create();
        $message = Message::factory()->create([
            'thread_id' => $thread->id,
            'to_id' => $user->id,
        ]);

        $gate = mock(ThreadSubjectGateContract::class);

        $this->gateProvider->shouldReceive('createGate')->once()->with('a_type')->andReturn($gate);
        $gate->shouldReceive('authorizeThread')->once()->with($user, $thread)->andReturnTrue();

        $this->assertTrue($this->policy->update($user, $user, $thread, $message));
    }
}
