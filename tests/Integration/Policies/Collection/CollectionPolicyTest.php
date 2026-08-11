<?php

declare(strict_types=1);

namespace Polis\Tests\Integration\Policies\Collection;

use App\Models\Collection\Collection;
use App\Models\Organization\Organization;
use App\Models\Organization\OrganizationManager;
use App\Models\User\User;
use App\Policies\Collection\CollectionPolicy;
use Polis\Tests\Application\ApplicationTestCase;

final class CollectionPolicyTest extends ApplicationTestCase
{
    
    public function test_all_passes(): void
    {
        $policy = new CollectionPolicy;

        $this->assertTrue($policy->all(new User));
    }

    public function test_create_blocks_different_user(): void
    {
        $policy = new CollectionPolicy;

        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->assertFalse($policy->create($user, $otherUser));
    }

    public function test_create_blocks_user_out_of_organization(): void
    {
        $policy = new CollectionPolicy;

        $user = User::factory()->create();
        $organization = Organization::factory()->create();

        $this->assertFalse($policy->create($user, $organization));
    }

    public function test_create_passes_with_matching_user(): void
    {
        $policy = new CollectionPolicy;

        $user = User::factory()->create();

        $this->assertTrue($policy->create($user, $user));
    }

    public function test_creates_passes_in_organization(): void
    {
        $policy = new CollectionPolicy;

        $user = User::factory()->create();
        $organization = Organization::factory()->create();

        OrganizationManager::factory()->create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
        ]);

        $this->assertTrue($policy->create($user, $organization));
    }

    public function test_view_passes_with_public_collection(): void
    {
        $policy = new CollectionPolicy;

        $collection = Collection::factory()->create([
            'is_public' => true,
        ]);

        $this->assertTrue($policy->view(new User, $collection));
    }

    public function test_view_blocks_different_user(): void
    {
        $policy = new CollectionPolicy;

        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $collection = Collection::factory()->create([
            'is_public' => false,
            'owner_id' => $otherUser->id,
            'owner_type' => 'user',
        ]);

        $this->assertFalse($policy->view($user, $collection));
    }

    public function test_view_blocks_user_out_of_organization(): void
    {
        $policy = new CollectionPolicy;

        $user = User::factory()->create();
        $organization = Organization::factory()->create();

        $collection = Collection::factory()->create([
            'is_public' => false,
            'owner_id' => $organization->id,
            'owner_type' => 'organization',
        ]);

        $this->assertFalse($policy->view($user, $collection));
    }

    public function test_view_passes_with_matching_user(): void
    {
        $policy = new CollectionPolicy;

        $user = User::factory()->create();

        $collection = Collection::factory()->create([
            'is_public' => false,
            'owner_id' => $user->id,
            'owner_type' => 'user',
        ]);

        $this->assertTrue($policy->view($user, $collection));
    }

    public function test_view_passes_in_organization(): void
    {
        $policy = new CollectionPolicy;

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

        $this->assertTrue($policy->view($user, $collection));
    }

    public function test_update_blocks_different_user(): void
    {
        $policy = new CollectionPolicy;

        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $collection = Collection::factory()->create([
            'is_public' => false,
            'owner_id' => $otherUser->id,
            'owner_type' => 'user',
        ]);

        $this->assertFalse($policy->update($user, $collection));
    }

    public function test_update_blocks_user_out_of_organization(): void
    {
        $policy = new CollectionPolicy;

        $user = User::factory()->create();
        $organization = Organization::factory()->create();

        $collection = Collection::factory()->create([
            'is_public' => false,
            'owner_id' => $organization->id,
            'owner_type' => 'organization',
        ]);

        $this->assertFalse($policy->update($user, $collection));
    }

    public function test_update_passes_with_matching_user(): void
    {
        $policy = new CollectionPolicy;

        $user = User::factory()->create();

        $collection = Collection::factory()->create([
            'is_public' => false,
            'owner_id' => $user->id,
            'owner_type' => 'user',
        ]);

        $this->assertTrue($policy->update($user, $collection));
    }

    public function test_update_passes_in_organization(): void
    {
        $policy = new CollectionPolicy;

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

        $this->assertTrue($policy->update($user, $collection));
    }

    public function test_delete_blocks_different_user(): void
    {
        $policy = new CollectionPolicy;

        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $collection = Collection::factory()->create([
            'is_public' => false,
            'owner_id' => $otherUser->id,
            'owner_type' => 'user',
        ]);

        $this->assertFalse($policy->delete($user, $collection));
    }

    public function test_delete_blocks_user_out_of_organization(): void
    {
        $policy = new CollectionPolicy;

        $user = User::factory()->create();
        $organization = Organization::factory()->create();

        $collection = Collection::factory()->create([
            'is_public' => false,
            'owner_id' => $organization->id,
            'owner_type' => 'organization',
        ]);

        $this->assertFalse($policy->delete($user, $collection));
    }

    public function test_delete_passes_with_matching_user(): void
    {
        $policy = new CollectionPolicy;

        $user = User::factory()->create();

        $collection = Collection::factory()->create([
            'is_public' => false,
            'owner_id' => $user->id,
            'owner_type' => 'user',
        ]);

        $this->assertTrue($policy->delete($user, $collection));
    }

    public function test_delete_passes_in_organization(): void
    {
        $policy = new CollectionPolicy;

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

        $this->assertTrue($policy->delete($user, $collection));
    }
}
