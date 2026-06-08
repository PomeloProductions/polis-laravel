<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Policies;

use App\Models\Role;
use Mockery;
use Polis\Contracts\Models\IsAnEntityContract;
use Polis\Tests\Fixtures\Models\Collection;
use Polis\Tests\Fixtures\Models\CollectionItem;
use Polis\Tests\Fixtures\Policies\Collection\CollectionItemPolicy;
use Polis\Tests\Fixtures\Policies\Collection\CollectionPolicy;
use Polis\Tests\TestCase;

/**
 * Coverage for the Collection / CollectionItem abstract policies.
 *
 * Both abstracts route ownership checks through the collection owner's
 * canUserManageEntity(). is_public bypasses the owner check on view/all.
 * CollectionItem additionally enforces $collection->id == $item->collection_id.
 */
final class CollectionPolicyAbstractTest extends TestCase
{
    public function test_collection_all_returns_true_for_any_user(): void
    {
        $policy = new CollectionPolicy;
        $user = Mockery::mock('App\\Models\\User\\User');

        $this->assertTrue($policy->all($user));
    }

    public function test_collection_create_requires_manager_on_entity(): void
    {
        $policy = new CollectionPolicy;
        $user = Mockery::mock('App\\Models\\User\\User');
        $entity = Mockery::mock(IsAnEntityContract::class);
        $entity->shouldReceive('canUserManageEntity')->once()->with($user, Role::MANAGER)->andReturn(true);

        $this->assertTrue($policy->create($user, $entity));
    }

    public function test_collection_view_allows_when_collection_is_public(): void
    {
        $policy = new CollectionPolicy;
        $user = Mockery::mock('App\\Models\\User\\User');
        $owner = Mockery::mock(IsAnEntityContract::class);
        // No canUserManageEntity expectation — short-circuited by is_public.
        $collection = new Collection;
        $collection->is_public = true;
        $collection->owner = $owner;

        $this->assertTrue($policy->view($user, $collection));
    }

    public function test_collection_view_denies_when_private_and_user_cannot_manage(): void
    {
        $policy = new CollectionPolicy;
        $user = Mockery::mock('App\\Models\\User\\User');
        $owner = Mockery::mock(IsAnEntityContract::class);
        $owner->shouldReceive('canUserManageEntity')->once()->with($user, Role::MANAGER)->andReturn(false);
        $collection = new Collection;
        $collection->is_public = false;
        $collection->owner = $owner;

        $this->assertFalse($policy->view($user, $collection));
    }

    public function test_collection_update_requires_manager_on_owner(): void
    {
        $policy = new CollectionPolicy;
        $user = Mockery::mock('App\\Models\\User\\User');
        $owner = Mockery::mock(IsAnEntityContract::class);
        $owner->shouldReceive('canUserManageEntity')->once()->with($user, Role::MANAGER)->andReturn(true);
        $collection = new Collection;
        $collection->owner = $owner;

        $this->assertTrue($policy->update($user, $collection));
    }

    public function test_collection_delete_requires_manager_on_owner(): void
    {
        $policy = new CollectionPolicy;
        $user = Mockery::mock('App\\Models\\User\\User');
        $owner = Mockery::mock(IsAnEntityContract::class);
        $owner->shouldReceive('canUserManageEntity')->once()->with($user, Role::MANAGER)->andReturn(true);
        $collection = new Collection;
        $collection->owner = $owner;

        $this->assertTrue($policy->delete($user, $collection));
    }

    public function test_collection_delete_denies_when_user_cannot_manage_owner(): void
    {
        $policy = new CollectionPolicy;
        $user = Mockery::mock('App\\Models\\User\\User');
        $owner = Mockery::mock(IsAnEntityContract::class);
        $owner->shouldReceive('canUserManageEntity')->once()->with($user, Role::MANAGER)->andReturn(false);
        $collection = new Collection;
        $collection->owner = $owner;

        $this->assertFalse($policy->delete($user, $collection));
    }

    public function test_collection_item_all_allows_when_collection_public(): void
    {
        $policy = new CollectionItemPolicy;
        $user = Mockery::mock('App\\Models\\User\\User');
        $owner = Mockery::mock(IsAnEntityContract::class);
        $collection = new Collection;
        $collection->is_public = true;
        $collection->owner = $owner;

        $this->assertTrue($policy->all($user, $collection));
    }

    public function test_collection_item_all_falls_back_to_owner_manage_check(): void
    {
        $policy = new CollectionItemPolicy;
        $user = Mockery::mock('App\\Models\\User\\User');
        $owner = Mockery::mock(IsAnEntityContract::class);
        $owner->shouldReceive('canUserManageEntity')->once()->with($user, Role::MANAGER)->andReturn(true);
        $collection = new Collection;
        $collection->is_public = false;
        $collection->owner = $owner;

        $this->assertTrue($policy->all($user, $collection));
    }

    public function test_collection_item_create_requires_manager_on_owner(): void
    {
        $policy = new CollectionItemPolicy;
        $user = Mockery::mock('App\\Models\\User\\User');
        $owner = Mockery::mock(IsAnEntityContract::class);
        $owner->shouldReceive('canUserManageEntity')->once()->with($user, Role::MANAGER)->andReturn(true);
        $collection = new Collection;
        $collection->owner = $owner;

        $this->assertTrue($policy->create($user, $collection));
    }

    public function test_collection_item_view_allows_public_collection_with_matching_item(): void
    {
        $policy = new CollectionItemPolicy;
        $user = Mockery::mock('App\\Models\\User\\User');
        $owner = Mockery::mock(IsAnEntityContract::class);
        $collection = new Collection;
        $collection->id = 3;
        $collection->is_public = true;
        $collection->owner = $owner;
        $item = new CollectionItem;
        $item->collection_id = 3;

        $this->assertTrue($policy->view($user, $collection, $item));
    }

    public function test_collection_item_view_denies_when_item_collection_id_mismatches(): void
    {
        $policy = new CollectionItemPolicy;
        $user = Mockery::mock('App\\Models\\User\\User');
        $owner = Mockery::mock(IsAnEntityContract::class);
        $collection = new Collection;
        $collection->id = 3;
        $collection->is_public = true;
        $collection->owner = $owner;
        $item = new CollectionItem;
        $item->collection_id = 99; // collection-id mismatch

        $this->assertFalse($policy->view($user, $collection, $item));
    }

    public function test_collection_item_update_denies_on_collection_id_mismatch(): void
    {
        $policy = new CollectionItemPolicy;
        $user = Mockery::mock('App\\Models\\User\\User');
        $owner = Mockery::mock(IsAnEntityContract::class);
        $collection = new Collection;
        $collection->id = 3;
        $collection->owner = $owner;
        $item = new CollectionItem;
        $item->collection_id = 99;

        $this->assertFalse($policy->update($user, $collection, $item));
    }

    public function test_collection_item_update_allows_with_matching_collection_and_manager(): void
    {
        $policy = new CollectionItemPolicy;
        $user = Mockery::mock('App\\Models\\User\\User');
        $owner = Mockery::mock(IsAnEntityContract::class);
        $owner->shouldReceive('canUserManageEntity')->once()->with($user, Role::MANAGER)->andReturn(true);
        $collection = new Collection;
        $collection->id = 3;
        $collection->owner = $owner;
        $item = new CollectionItem;
        $item->collection_id = 3;

        $this->assertTrue($policy->update($user, $collection, $item));
    }

    public function test_collection_item_delete_allows_with_matching_collection_and_manager(): void
    {
        $policy = new CollectionItemPolicy;
        $user = Mockery::mock('App\\Models\\User\\User');
        $owner = Mockery::mock(IsAnEntityContract::class);
        $owner->shouldReceive('canUserManageEntity')->once()->with($user, Role::MANAGER)->andReturn(true);
        $collection = new Collection;
        $collection->id = 3;
        $collection->owner = $owner;
        $item = new CollectionItem;
        $item->collection_id = 3;

        $this->assertTrue($policy->delete($user, $collection, $item));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
