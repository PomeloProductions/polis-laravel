<?php

declare(strict_types=1);

namespace Polis\Tests\Integration\Policies;

use App\Models\Asset;
use App\Models\User\User;
use App\Policies\AssetPolicy;
use Polis\Tests\DatabaseSetupTrait;
use Polis\Tests\TestCase;

/**
 * Class AssetPolicyTest
 */
final class AssetPolicyTest extends TestCase
{
    use DatabaseSetupTrait;

    public function test_all_fails(): void
    {
        $policy = new AssetPolicy;

        $loggedInUser = User::factory()->create();
        $requestedUser = User::factory()->create();

        $this->assertFalse($policy->all($loggedInUser, $requestedUser));
    }

    public function test_all_passes(): void
    {
        $policy = new AssetPolicy;

        $user = User::factory()->create();

        $this->assertTrue($policy->all($user, $user));
    }

    public function test_create_fails(): void
    {
        $policy = new AssetPolicy;

        $loggedInUser = User::factory()->create();
        $requestedUser = User::factory()->create();

        $this->assertFalse($policy->create($loggedInUser, $requestedUser));
    }

    public function test_create_passes(): void
    {
        $policy = new AssetPolicy;

        $user = User::factory()->create();

        $this->assertTrue($policy->create($user, $user));
    }

    public function test_update_fails_user_mismatch(): void
    {
        $policy = new AssetPolicy;

        $loggedInUser = User::factory()->create();
        $requestedUser = User::factory()->create();
        $asset = Asset::factory()->create([
            'owner_id' => $loggedInUser->id,
        ]);

        $this->assertFalse($policy->update($loggedInUser, $requestedUser, $asset));
    }

    public function test_update_fails_asset_mismatch(): void
    {
        $policy = new AssetPolicy;

        $user = User::factory()->create();
        $asset = Asset::factory()->create();

        $this->assertFalse($policy->update($user, $user, $asset));
    }

    public function test_update_passes(): void
    {
        $policy = new AssetPolicy;

        $user = User::factory()->create();
        $asset = Asset::factory()->create([
            'owner_id' => $user->id,
            'owner_type' => 'user',
        ]);

        $this->assertTrue($policy->update($user, $user, $asset));
    }

    public function test_delete_fails_user_mismatch(): void
    {
        $policy = new AssetPolicy;

        $loggedInUser = User::factory()->create();
        $requestedUser = User::factory()->create();
        $asset = Asset::factory()->create([
            'owner_id' => $loggedInUser->id,
            'owner_type' => 'user',
        ]);

        $this->assertFalse($policy->delete($loggedInUser, $requestedUser, $asset));
    }

    public function test_delete_fails_asset_mismatch(): void
    {
        $policy = new AssetPolicy;

        $user = User::factory()->create();
        $asset = Asset::factory()->create();

        $this->assertFalse($policy->delete($user, $user, $asset));
    }

    public function test_delete_passes(): void
    {
        $policy = new AssetPolicy;

        $user = User::factory()->create();
        $asset = Asset::factory()->create([
            'owner_id' => $user->id,
            'owner_type' => 'user',
        ]);

        $this->assertTrue($policy->delete($user, $user, $asset));
    }
}
