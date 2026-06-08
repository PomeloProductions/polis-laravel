<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Policies;

use App\Models\Role;
use Mockery;
use Polis\Tests\Fixtures\Policies\ResourcePolicy;
use Polis\Tests\TestCase;

/**
 * Standalone coverage for Polis\Policies\BasePolicyAbstract::before().
 *
 * before() is invoked by Laravel before any gate method; returning true
 * grants access, null defers to the specific gate, and false denies
 * outright. The abstract's policy is: super-admins always pass, everyone
 * else defers to the specific gate.
 *
 * We exercise the abstract via the empty ResourcePolicy fixture (the
 * cheapest concrete subclass — its own gates are trivial true/false).
 */
final class BasePolicyAbstractTest extends TestCase
{
    public function test_before_returns_true_for_super_admin(): void
    {
        $policy = new ResourcePolicy;

        $user = Mockery::mock('App\\Models\\User\\User');
        $user->shouldReceive('hasRole')
            ->once()
            ->with([Role::SUPER_ADMIN])
            ->andReturn(true);

        $this->assertTrue($policy->before($user));
    }

    public function test_before_returns_null_for_non_super_admin(): void
    {
        $policy = new ResourcePolicy;

        $user = Mockery::mock('App\\Models\\User\\User');
        $user->shouldReceive('hasRole')
            ->once()
            ->with([Role::SUPER_ADMIN])
            ->andReturn(false);

        $this->assertNull($policy->before($user));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
