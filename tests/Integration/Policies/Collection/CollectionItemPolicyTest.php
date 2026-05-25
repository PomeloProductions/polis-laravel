<?php

declare(strict_types=1);

namespace Polis\Tests\Integration\Policies\Collection;

use App\Models\Collection\Collection;
use App\Models\Collection\CollectionItem;
use App\Models\Organization\Organization;
use App\Models\Organization\OrganizationManager;
use App\Models\User\User;
use App\Policies\Collection\CollectionItemPolicy;
use Polis\Tests\DatabaseSetupTrait;
use Polis\Tests\TestCase;

final class CollectionItemPolicyTest extends TestCase
{
    use DatabaseSetupTrait;

    public function test_all_passes_with_public_collection(): void
    {
        $policy = new CollectionItemPolicy;

        $collection = Collection::factory()->create([
            'is_public' => true,
        ]);

        $this->assertTrue($policy->all(new User, $collection));
    }

    public function test_all_blocks_different_user(): void
    {
        $policy = new CollectionItemPolicy;

        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $collection = Collection::factory()->create([
            'is_public' => false,
            'owner_id' => $otherUser->id,
            'owner_type' => 'user',
        ]);

        $this->assertFalse($policy->all($user, $collection));
    }

    public function test_all_blocks_user_out_of_organization(): void
    {
        $policy = new CollectionItemPolicy;

        $user = User::factory()->create();
        $organization = Organization::factory()->create();

        $collection = Collection::factory()->create([
            'is_public' => false,
            'owner_id' => $organization->id,
            'owner_type' => 'organization',
        ]);

        $this->assertFalse($policy->all($user, $collection));
    }

    public function test_all_passes_with_matching_user(): void
    {
        $policy = new CollectionItemPolicy;

        $user = User::factory()->create();

        $collection = Collection::factory()->create([
            'is_public' => false,
            'owner_id' => $user->id,
            'owner_type' => 'user',
        ]);

        $this->assertTrue($policy->all($user, $collection));
    }

    public function test_all_passes_in_organization(): void
    {
        $policy = new CollectionItemPolicy;

        $user = User::factory()->create();
        $organization = Organization::factory()->create();

        OrganizationManager::factory()->create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
        ]);

        $collection = Collection::factory()->create([
            'is_public' => false,
            'owner_id' => $organization->id,
            'owner_type' => 'organization',
        ]);

        $this->assertTrue($policy->all($user, $collection));
    }

    public function test_create_blocks_different_user(): void
    {
        $policy = new CollectionItemPolicy;

        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $collection = Collection::factory()->create([
            'is_public' => false,
            'owner_id' => $otherUser->id,
            'owner_type' => 'user',
        ]);

        $this->assertFalse($policy->create($user, $collection));
    }

    public function test_create_blocks_user_out_of_organization(): void
    {
        $policy = new CollectionItemPolicy;

        $user = User::factory()->create();
        $organization = Organization::factory()->create();

        $collection = Collection::factory()->create([
            'is_public' => false,
            'owner_id' => $organization->id,
            'owner_type' => 'organization',
        ]);

        $this->assertFalse($policy->create($user, $collection));
    }

    public function test_create_passes_with_matching_user(): void
    {
        $policy = new CollectionItemPolicy;

        $user = User::factory()->create();

        $collection = Collection::factory()->create([
            'is_public' => false,
            'owner_id' => $user->id,
            'owner_type' => 'user',
        ]);

        $this->assertTrue($policy->create($user, $collection));
    }

    public function test_create_passes_in_organization(): void
    {
        $policy = new CollectionItemPolicy;

        $user = User::factory()->create();
        $organization = Organization::factory()->create();

        OrganizationManager::factory()->create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
        ]);

        $collection = Collection::factory()->create([
            'is_public' => false,
            'owner_id' => $organization->id,
            'owner_type' => 'organization',
        ]);

        $this->assertTrue($policy->create($user, $collection));
    }

    public function test_view_fails_id_mismatch(): void
    {
        $policy = new CollectionItemPolicy;

        $collection = Collection::factory()->create([
            'is_public' => true,
        ]);
        $collectionItem = CollectionItem::factory()->create();

        $this->assertFalse($policy->view(new User, $collection, $collectionItem));
    }

    public function test_view_passes_with_public_collection(): void
    {
        $policy = new CollectionItemPolicy;

        $collection = Collection::factory()->create([
            'is_public' => true,
        ]);
        $collectionItem = CollectionItem::factory()->create([
            'collection_id' => $collection->id,
        ]);

        $this->assertTrue($policy->view(new User, $collection, $collectionItem));
    }

    public function test_view_blocks_different_user(): void
    {
        $policy = new CollectionItemPolicy;

        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $collection = Collection::factory()->create([
            'is_public' => false,
            'owner_id' => $otherUser->id,
            'owner_type' => 'user',
        ]);
        $collectionItem = CollectionItem::factory()->create([
            'collection_id' => $collection->id,
        ]);

        $this->assertFalse($policy->view($user, $collection, $collectionItem));
    }

    public function test_view_blocks_user_out_of_organization(): void
    {
        $policy = new CollectionItemPolicy;

        $user = User::factory()->create();
        $organization = Organization::factory()->create();

        $collection = Collection::factory()->create([
            'is_public' => false,
            'owner_id' => $organization->id,
            'owner_type' => 'organization',
        ]);
        $collectionItem = CollectionItem::factory()->create([
            'collection_id' => $collection->id,
        ]);

        $this->assertFalse($policy->view($user, $collection, $collectionItem));
    }

    public function test_view_passes_with_matching_user(): void
    {
        $policy = new CollectionItemPolicy;

        $user = User::factory()->create();

        $collection = Collection::factory()->create([
            'is_public' => false,
            'owner_id' => $user->id,
            'owner_type' => 'user',
        ]);
        $collectionItem = CollectionItem::factory()->create([
            'collection_id' => $collection->id,
        ]);

        $this->assertTrue($policy->view($user, $collection, $collectionItem));
    }

    public function test_view_passes_in_organization(): void
    {
        $policy = new CollectionItemPolicy;

        $user = User::factory()->create();
        $organization = Organization::factory()->create();

        OrganizationManager::factory()->create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
        ]);

        $collection = Collection::factory()->create([
            'is_public' => false,
            'owner_id' => $organization->id,
            'owner_type' => 'organization',
        ]);
        $collectionItem = CollectionItem::factory()->create([
            'collection_id' => $collection->id,
        ]);

        $this->assertTrue($policy->view($user, $collection, $collectionItem));
    }

    public function test_update_fails_id_mismatch(): void
    {
        $policy = new CollectionItemPolicy;

        $collection = Collection::factory()->create([
            'is_public' => true,
        ]);
        $collectionItem = CollectionItem::factory()->create();

        $this->assertFalse($policy->update(new User, $collection, $collectionItem));
    }

    public function test_update_blocks_different_user(): void
    {
        $policy = new CollectionItemPolicy;

        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $collection = Collection::factory()->create([
            'is_public' => false,
            'owner_id' => $otherUser->id,
            'owner_type' => 'user',
        ]);
        $collectionItem = CollectionItem::factory()->create([
            'collection_id' => $collection->id,
        ]);

        $this->assertFalse($policy->update($user, $collection, $collectionItem));
    }

    public function test_update_blocks_user_out_of_organization(): void
    {
        $policy = new CollectionItemPolicy;

        $user = User::factory()->create();
        $organization = Organization::factory()->create();

        $collection = Collection::factory()->create([
            'is_public' => false,
            'owner_id' => $organization->id,
            'owner_type' => 'organization',
        ]);
        $collectionItem = CollectionItem::factory()->create([
            'collection_id' => $collection->id,
        ]);

        $this->assertFalse($policy->update($user, $collection, $collectionItem));
    }

    public function test_update_passes_with_matching_user(): void
    {
        $policy = new CollectionItemPolicy;

        $user = User::factory()->create();

        $collection = Collection::factory()->create([
            'is_public' => false,
            'owner_id' => $user->id,
            'owner_type' => 'user',
        ]);
        $collectionItem = CollectionItem::factory()->create([
            'collection_id' => $collection->id,
        ]);

        $this->assertTrue($policy->update($user, $collection, $collectionItem));
    }

    public function test_update_passes_in_organization(): void
    {
        $policy = new CollectionItemPolicy;

        $user = User::factory()->create();
        $organization = Organization::factory()->create();

        OrganizationManager::factory()->create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
        ]);

        $collection = Collection::factory()->create([
            'is_public' => false,
            'owner_id' => $organization->id,
            'owner_type' => 'organization',
        ]);
        $collectionItem = CollectionItem::factory()->create([
            'collection_id' => $collection->id,
        ]);

        $this->assertTrue($policy->update($user, $collection, $collectionItem));
    }

    public function test_delete_fails_id_mismatch(): void
    {
        $policy = new CollectionItemPolicy;

        $collection = Collection::factory()->create([
            'is_public' => true,
        ]);
        $collectionItem = CollectionItem::factory()->create();

        $this->assertFalse($policy->delete(new User, $collection, $collectionItem));
    }

    public function test_delete_blocks_different_user(): void
    {
        $policy = new CollectionItemPolicy;

        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $collection = Collection::factory()->create([
            'is_public' => false,
            'owner_id' => $otherUser->id,
            'owner_type' => 'user',
        ]);
        $collectionItem = CollectionItem::factory()->create([
            'collection_id' => $collection->id,
        ]);

        $this->assertFalse($policy->delete($user, $collection, $collectionItem));
    }

    public function test_delete_blocks_user_out_of_organization(): void
    {
        $policy = new CollectionItemPolicy;

        $user = User::factory()->create();
        $organization = Organization::factory()->create();

        $collection = Collection::factory()->create([
            'is_public' => false,
            'owner_id' => $organization->id,
            'owner_type' => 'organization',
        ]);
        $collectionItem = CollectionItem::factory()->create([
            'collection_id' => $collection->id,
        ]);

        $this->assertFalse($policy->delete($user, $collection, $collectionItem));
    }

    public function test_delete_passes_with_matching_user(): void
    {
        $policy = new CollectionItemPolicy;

        $user = User::factory()->create();

        $collection = Collection::factory()->create([
            'is_public' => false,
            'owner_id' => $user->id,
            'owner_type' => 'user',
        ]);
        $collectionItem = CollectionItem::factory()->create([
            'collection_id' => $collection->id,
        ]);

        $this->assertTrue($policy->delete($user, $collection, $collectionItem));
    }

    public function test_delete_passes_in_organization(): void
    {
        $policy = new CollectionItemPolicy;

        $user = User::factory()->create();
        $organization = Organization::factory()->create();

        OrganizationManager::factory()->create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
        ]);

        $collection = Collection::factory()->create([
            'is_public' => false,
            'owner_id' => $organization->id,
            'owner_type' => 'organization',
        ]);
        $collectionItem = CollectionItem::factory()->create([
            'collection_id' => $collection->id,
        ]);

        $this->assertTrue($policy->delete($user, $collection, $collectionItem));
    }
}
