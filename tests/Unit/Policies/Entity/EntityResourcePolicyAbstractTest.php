<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Policies\Entity;

use App\Models\Role;
use Mockery;
use Polis\Contracts\Models\IsAnEntityContract;
use Polis\Tests\Fixtures\Policies\Entity\EntityResourcePolicy;
use Polis\Tests\TestCase;

/**
 * Coverage for EntityResourcePolicyAbstract — the reusable authorization base
 * for polymorphic-owner (owner_id/owner_type) resources. It authorizes against
 * any IsAnEntityContract via canUserManageEntity(), so a User-owned and an
 * Organization-owned resource share one policy. view()/update()/delete() read
 * the owning entity off the model's polymorphic `owner` relation.
 */
final class EntityResourcePolicyAbstractTest extends TestCase
{
    public function test_before_returns_true_for_super_admin(): void
    {
        $policy = new EntityResourcePolicy;
        $user = Mockery::mock('App\\Models\\User\\User');
        $user->shouldReceive('hasRole')->once()->with([Role::SUPER_ADMIN])->andReturn(true);

        $this->assertTrue($policy->before($user));
    }

    public function test_all_allows_any_authenticated_user(): void
    {
        $policy = new EntityResourcePolicy;
        $user = Mockery::mock('App\\Models\\User\\User');

        $this->assertTrue($policy->all($user));
    }

    public function test_create_delegates_to_entity_management(): void
    {
        $policy = new EntityResourcePolicy;
        $user = Mockery::mock('App\\Models\\User\\User');
        $entity = Mockery::mock(IsAnEntityContract::class);
        $entity->shouldReceive('canUserManageEntity')->once()->with($user, Role::MANAGER)->andReturn(true);

        $this->assertTrue($policy->create($user, $entity));
    }

    public function test_view_allows_public_resource_without_checking_entity(): void
    {
        $policy = new EntityResourcePolicy;
        $user = Mockery::mock('App\\Models\\User\\User');

        $model = new \stdClass;
        $model->is_public = true;
        $model->owner = null; // must not be consulted for a public resource

        $this->assertTrue($policy->view($user, $model));
    }

    public function test_view_allows_manager_of_owning_entity_for_private_resource(): void
    {
        $policy = new EntityResourcePolicy;
        $user = Mockery::mock('App\\Models\\User\\User');
        $entity = Mockery::mock(IsAnEntityContract::class);
        $entity->shouldReceive('canUserManageEntity')->once()->with($user, Role::MANAGER)->andReturn(true);

        $model = new \stdClass;
        $model->is_public = false;
        $model->owner = $entity;

        $this->assertTrue($policy->view($user, $model));
    }

    public function test_view_denies_non_manager_of_owning_entity(): void
    {
        $policy = new EntityResourcePolicy;
        $user = Mockery::mock('App\\Models\\User\\User');
        $entity = Mockery::mock(IsAnEntityContract::class);
        $entity->shouldReceive('canUserManageEntity')->once()->with($user, Role::MANAGER)->andReturn(false);

        $model = new \stdClass;
        $model->is_public = false;
        $model->owner = $entity;

        $this->assertFalse($policy->view($user, $model));
    }

    public function test_update_delegates_to_owning_entity_management(): void
    {
        $policy = new EntityResourcePolicy;
        $user = Mockery::mock('App\\Models\\User\\User');
        $entity = Mockery::mock(IsAnEntityContract::class);
        $entity->shouldReceive('canUserManageEntity')->once()->with($user, Role::MANAGER)->andReturn(true);

        $model = new \stdClass;
        $model->owner = $entity;

        $this->assertTrue($policy->update($user, $model));
    }

    public function test_delete_denies_when_owner_is_missing(): void
    {
        $policy = new EntityResourcePolicy;
        $user = Mockery::mock('App\\Models\\User\\User');

        $model = new \stdClass;
        $model->owner = null;

        $this->assertFalse($policy->delete($user, $model));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
