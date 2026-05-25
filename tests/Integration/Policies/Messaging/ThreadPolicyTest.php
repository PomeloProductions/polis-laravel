<?php

declare(strict_types=1);

namespace Polis\Tests\Integration\Policies\Messaging;

use App\Models\User\User;
use App\Policies\Messaging\ThreadPolicy;
use Polis\Contracts\ThreadSecurity\ThreadSubjectGateContract;
use Polis\Contracts\ThreadSecurity\ThreadSubjectGateProviderContract;
use Polis\Tests\CustomMockInterface;
use Polis\Tests\DatabaseSetupTrait;
use Polis\Tests\TestCase;

/**
 * Class ThreadPolicyTest
 */
final class ThreadPolicyTest extends TestCase
{
    use DatabaseSetupTrait;

    /**
     * @var ThreadSubjectGateProviderContract|CustomMockInterface
     */
    private $gateProvider;

    /**
     * @var ThreadPolicy
     */
    private $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();
        $this->gateProvider = mock(ThreadSubjectGateProviderContract::class);
        $this->policy = new ThreadPolicy($this->gateProvider);
    }

    public function test_all_blocks_when_gate_not_found(): void
    {
        $loggedInUser = User::factory()->create();
        $requestedUser = User::factory()->create();

        $this->gateProvider->shouldReceive('createGate')->once()->with('a_type')->andReturnNull();

        $this->assertFalse($this->policy->all($loggedInUser, $requestedUser, 'a_type'));
    }

    public function test_all_block_when_accessing_another_user(): void
    {
        $loggedInUser = User::factory()->create();
        $requestedUser = User::factory()->create();

        $gate = mock(ThreadSubjectGateContract::class);

        $this->gateProvider->shouldReceive('createGate')->once()->with('a_type')->andReturn($gate);

        $this->assertFalse($this->policy->all($loggedInUser, $requestedUser, 'a_type'));
    }

    public function test_all_block_when_gate_fails(): void
    {
        $loggedInUser = User::factory()->create();

        $gate = mock(ThreadSubjectGateContract::class);

        $this->gateProvider->shouldReceive('createGate')->once()->with('a_type')->andReturn($gate);
        $gate->shouldReceive('authorizeSubject')->once()->with($loggedInUser, 43)->andReturnFalse();

        $this->assertFalse($this->policy->all($loggedInUser, $loggedInUser, 'a_type', 43));
    }

    public function test_all_passes(): void
    {
        $loggedInUser = User::factory()->create();

        $gate = mock(ThreadSubjectGateContract::class);

        $this->gateProvider->shouldReceive('createGate')->once()->with('a_type')->andReturn($gate);
        $gate->shouldReceive('authorizeSubject')->once()->with($loggedInUser, 43)->andReturnTrue();

        $this->assertTrue($this->policy->all($loggedInUser, $loggedInUser, 'a_type', 43));
    }

    public function test_create_blocks_when_gate_not_found(): void
    {
        $loggedInUser = User::factory()->create();
        $requestedUser = User::factory()->create();

        $this->gateProvider->shouldReceive('createGate')->once()->with('a_type')->andReturnNull();

        $this->assertFalse($this->policy->create($loggedInUser, $requestedUser, 'a_type'));
    }

    public function test_create_block_when_accessing_another_user(): void
    {
        $loggedInUser = User::factory()->create();
        $requestedUser = User::factory()->create();

        $gate = mock(ThreadSubjectGateContract::class);

        $this->gateProvider->shouldReceive('createGate')->once()->with('a_type')->andReturn($gate);

        $this->assertFalse($this->policy->create($loggedInUser, $requestedUser, 'a_type'));
    }

    public function test_create_block_when_gate_fails(): void
    {
        $loggedInUser = User::factory()->create();

        $gate = mock(ThreadSubjectGateContract::class);

        $this->gateProvider->shouldReceive('createGate')->once()->with('a_type')->andReturn($gate);
        $gate->shouldReceive('authorizeSubject')->once()->with($loggedInUser, 43)->andReturnFalse();

        $this->assertFalse($this->policy->create($loggedInUser, $loggedInUser, 'a_type', 43));
    }

    public function test_create_passes(): void
    {
        $loggedInUser = User::factory()->create();

        $gate = mock(ThreadSubjectGateContract::class);

        $this->gateProvider->shouldReceive('createGate')->once()->with('a_type')->andReturn($gate);
        $gate->shouldReceive('authorizeSubject')->once()->with($loggedInUser, 43)->andReturnTrue();

        $this->assertTrue($this->policy->create($loggedInUser, $loggedInUser, 'a_type', 43));
    }
}
