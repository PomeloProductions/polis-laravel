<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Policies;

use App\Models\Role;
use Mockery;
use Polis\Tests\Fixtures\Policies\Statistic\StatisticPolicy;
use Polis\Tests\TestCase;

/**
 * Coverage for StatisticPolicyAbstract — every gate (other than all()
 * which is just `true`) is a thin hasRole() lookup. view() accepts both
 * CONTENT_EDITOR and SUPPORT_STAFF; create/update/delete only allow
 * CONTENT_EDITOR.
 */
final class StatisticPolicyAbstractTest extends TestCase
{
    public function test_all_returns_true(): void
    {
        $policy = new StatisticPolicy;
        $this->assertTrue($policy->all(Mockery::mock('App\\Models\\User\\User')));
    }

    public function test_view_allows_content_editor_or_support_staff(): void
    {
        $policy = new StatisticPolicy;
        $user = Mockery::mock('App\\Models\\User\\User');
        $user->shouldReceive('hasRole')
            ->once()
            ->with([Role::CONTENT_EDITOR, Role::SUPPORT_STAFF])
            ->andReturn(true);

        $this->assertTrue($policy->view($user));
    }

    public function test_view_denies_other_roles(): void
    {
        $policy = new StatisticPolicy;
        $user = Mockery::mock('App\\Models\\User\\User');
        $user->shouldReceive('hasRole')
            ->once()
            ->with([Role::CONTENT_EDITOR, Role::SUPPORT_STAFF])
            ->andReturn(false);

        $this->assertFalse($policy->view($user));
    }

    public function test_create_requires_content_editor(): void
    {
        $policy = new StatisticPolicy;
        $user = Mockery::mock('App\\Models\\User\\User');
        $user->shouldReceive('hasRole')
            ->once()
            ->with([Role::CONTENT_EDITOR])
            ->andReturn(true);

        $this->assertTrue($policy->create($user));
    }

    public function test_update_requires_content_editor(): void
    {
        $policy = new StatisticPolicy;
        $user = Mockery::mock('App\\Models\\User\\User');
        $user->shouldReceive('hasRole')
            ->once()
            ->with([Role::CONTENT_EDITOR])
            ->andReturn(false);

        $this->assertFalse($policy->update($user));
    }

    public function test_delete_requires_content_editor(): void
    {
        $policy = new StatisticPolicy;
        $user = Mockery::mock('App\\Models\\User\\User');
        $user->shouldReceive('hasRole')
            ->once()
            ->with([Role::CONTENT_EDITOR])
            ->andReturn(true);

        $this->assertTrue($policy->delete($user));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
