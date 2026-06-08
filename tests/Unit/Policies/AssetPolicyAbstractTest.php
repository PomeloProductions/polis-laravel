<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Policies;

use Mockery;
use Polis\Contracts\Models\IsAnEntityContract;
use Polis\Tests\Fixtures\Models\Asset;
use Polis\Tests\Fixtures\Policies\AssetPolicy;
use Polis\Tests\TestCase;

/**
 * Coverage for AssetPolicyAbstract. Update / delete require both the
 * owner_type / owner_id match AND the user has manage permission on the
 * entity, so each negative branch is tested independently.
 */
final class AssetPolicyAbstractTest extends TestCase
{
    public function test_all_delegates_to_entity_can_user_manage(): void
    {
        $policy = new AssetPolicy;
        $user = Mockery::mock('App\\Models\\User\\User');
        $entity = Mockery::mock(IsAnEntityContract::class);
        $entity->shouldReceive('canUserManageEntity')->once()->with($user)->andReturn(true);

        $this->assertTrue($policy->all($user, $entity));
    }

    public function test_all_denies_when_user_cannot_manage_entity(): void
    {
        $policy = new AssetPolicy;
        $user = Mockery::mock('App\\Models\\User\\User');
        $entity = Mockery::mock(IsAnEntityContract::class);
        $entity->shouldReceive('canUserManageEntity')->once()->with($user)->andReturn(false);

        $this->assertFalse($policy->all($user, $entity));
    }

    public function test_create_delegates_to_entity_can_user_manage(): void
    {
        $policy = new AssetPolicy;
        $user = Mockery::mock('App\\Models\\User\\User');
        $entity = Mockery::mock(IsAnEntityContract::class);
        $entity->shouldReceive('canUserManageEntity')->once()->with($user)->andReturn(true);

        $this->assertTrue($policy->create($user, $entity));
    }

    public function test_update_allows_when_owner_matches_and_user_can_manage(): void
    {
        $policy = new AssetPolicy;
        $user = Mockery::mock('App\\Models\\User\\User');
        $entity = Mockery::mock(IsAnEntityContract::class);
        $entity->id = 9;
        $entity->shouldReceive('morphRelationName')->andReturn('user');
        $entity->shouldReceive('canUserManageEntity')->once()->with($user)->andReturn(true);

        $asset = new Asset;
        $asset->owner_type = 'user';
        $asset->owner_id = 9;

        $this->assertTrue($policy->update($user, $entity, $asset));
    }

    public function test_update_denies_when_owner_type_differs(): void
    {
        $policy = new AssetPolicy;
        $user = Mockery::mock('App\\Models\\User\\User');
        $entity = Mockery::mock(IsAnEntityContract::class);
        $entity->id = 9;
        $entity->shouldReceive('morphRelationName')->andReturn('user');

        $asset = new Asset;
        $asset->owner_type = 'organization'; // different morph relation
        $asset->owner_id = 9;

        $this->assertFalse($policy->update($user, $entity, $asset));
    }

    public function test_update_denies_when_owner_id_differs(): void
    {
        $policy = new AssetPolicy;
        $user = Mockery::mock('App\\Models\\User\\User');
        $entity = Mockery::mock(IsAnEntityContract::class);
        $entity->id = 9;
        $entity->shouldReceive('morphRelationName')->andReturn('user');

        $asset = new Asset;
        $asset->owner_type = 'user';
        $asset->owner_id = 99;

        $this->assertFalse($policy->update($user, $entity, $asset));
    }

    public function test_delete_allows_when_owner_matches_and_user_can_manage(): void
    {
        $policy = new AssetPolicy;
        $user = Mockery::mock('App\\Models\\User\\User');
        $entity = Mockery::mock(IsAnEntityContract::class);
        $entity->id = 9;
        $entity->shouldReceive('morphRelationName')->andReturn('user');
        $entity->shouldReceive('canUserManageEntity')->once()->with($user)->andReturn(true);

        $asset = new Asset;
        $asset->owner_type = 'user';
        $asset->owner_id = 9;

        $this->assertTrue($policy->delete($user, $entity, $asset));
    }

    public function test_delete_denies_when_user_cannot_manage(): void
    {
        $policy = new AssetPolicy;
        $user = Mockery::mock('App\\Models\\User\\User');
        $entity = Mockery::mock(IsAnEntityContract::class);
        $entity->id = 9;
        $entity->shouldReceive('morphRelationName')->andReturn('user');
        $entity->shouldReceive('canUserManageEntity')->once()->with($user)->andReturn(false);

        $asset = new Asset;
        $asset->owner_type = 'user';
        $asset->owner_id = 9;

        $this->assertFalse($policy->delete($user, $entity, $asset));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
