<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Policies\Entity;

use App\Models\Role;
use Mockery;
use Polis\Contracts\Models\IsAnEntityContract;
use Polis\Tests\Fixtures\Policies\Entity\EntityArticlePolicy;
use Polis\Tests\TestCase;

/**
 * Coverage for EntityArticlePolicyAbstract — the entity-generic Article
 * ("contract") policy. Unlike OrganizationArticlePolicyAbstract (which is bound
 * to a concrete Organization + organization_id FK), this authorizes against any
 * IsAnEntityContract via canUserManageEntity(), so the SAME policy governs a
 * User-owned or Organization-owned article listing.
 */
final class EntityArticlePolicyAbstractTest extends TestCase
{
    public function test_before_returns_true_for_super_admin(): void
    {
        $policy = new EntityArticlePolicy;
        $user = Mockery::mock('App\\Models\\User\\User');
        $user->shouldReceive('hasRole')->once()->with([Role::SUPER_ADMIN])->andReturn(true);

        $this->assertTrue($policy->before($user));
    }

    public function test_before_defers_for_non_super_admin(): void
    {
        $policy = new EntityArticlePolicy;
        $user = Mockery::mock('App\\Models\\User\\User');
        $user->shouldReceive('hasRole')->once()->with([Role::SUPER_ADMIN])->andReturn(false);

        $this->assertNull($policy->before($user));
    }

    public function test_all_allows_manager_of_entity(): void
    {
        $policy = new EntityArticlePolicy;
        $user = Mockery::mock('App\\Models\\User\\User');
        $entity = Mockery::mock(IsAnEntityContract::class);
        $entity->shouldReceive('canUserManageEntity')->once()->with($user, Role::MANAGER)->andReturn(true);

        $this->assertTrue($policy->all($user, $entity));
    }

    public function test_all_denies_non_manager_of_entity(): void
    {
        $policy = new EntityArticlePolicy;
        $user = Mockery::mock('App\\Models\\User\\User');
        $entity = Mockery::mock(IsAnEntityContract::class);
        $entity->shouldReceive('canUserManageEntity')->once()->with($user, Role::MANAGER)->andReturn(false);

        $this->assertFalse($policy->all($user, $entity));
    }

    public function test_create_allows_manager_of_entity(): void
    {
        $policy = new EntityArticlePolicy;
        $user = Mockery::mock('App\\Models\\User\\User');
        $entity = Mockery::mock(IsAnEntityContract::class);
        $entity->shouldReceive('canUserManageEntity')->once()->with($user, Role::MANAGER)->andReturn(true);

        $this->assertTrue($policy->create($user, $entity));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
