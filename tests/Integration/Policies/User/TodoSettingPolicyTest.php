<?php

declare(strict_types=1);

namespace Polis\Tests\Integration\Policies\User;

use App\Models\User\TodoSetting;
use App\Models\User\User;
use App\Policies\User\TodoSettingPolicy;
use Polis\Tests\DatabaseSetupTrait;
use Polis\Tests\TestCase;

final class TodoSettingPolicyTest extends TestCase
{
    use DatabaseSetupTrait;

    public function test_all_passes(): void
    {
        $user = User::factory()->create();

        $policy = new TodoSettingPolicy;

        $this->assertTrue($policy->all($user, $user));
    }

    public function test_all_fails(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $policy = new TodoSettingPolicy;

        $this->assertFalse($policy->all($user1, $user2));
    }

    public function test_view_passes(): void
    {
        $user = User::factory()->create();
        $setting = TodoSetting::factory()->create([
            'user_id' => $user->id,
        ]);

        $policy = new TodoSettingPolicy;

        $this->assertTrue($policy->view($user, $user, $setting));
    }

    public function test_view_fails_different_logged_in_user(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $setting = TodoSetting::factory()->create([
            'user_id' => $user2->id,
        ]);

        $policy = new TodoSettingPolicy;

        $this->assertFalse($policy->view($user1, $user2, $setting));
    }

    public function test_create_passes(): void
    {
        $user = User::factory()->create();

        $policy = new TodoSettingPolicy;

        $this->assertTrue($policy->create($user, $user));
    }

    public function test_create_fails(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $policy = new TodoSettingPolicy;

        $this->assertFalse($policy->create($user1, $user2));
    }

    public function test_update_passes(): void
    {
        $user = User::factory()->create();
        $setting = TodoSetting::factory()->create([
            'user_id' => $user->id,
        ]);

        $policy = new TodoSettingPolicy;

        $this->assertTrue($policy->update($user, $user, $setting));
    }

    public function test_update_fails_different_logged_in_user(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $setting = TodoSetting::factory()->create([
            'user_id' => $user2->id,
        ]);

        $policy = new TodoSettingPolicy;

        $this->assertFalse($policy->update($user1, $user2, $setting));
    }

    public function test_delete_passes(): void
    {
        $user = User::factory()->create();
        $setting = TodoSetting::factory()->create([
            'user_id' => $user->id,
        ]);

        $policy = new TodoSettingPolicy;

        $this->assertTrue($policy->delete($user, $user, $setting));
    }

    public function test_delete_fails_different_logged_in_user(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $setting = TodoSetting::factory()->create([
            'user_id' => $user2->id,
        ]);

        $policy = new TodoSettingPolicy;

        $this->assertFalse($policy->delete($user1, $user2, $setting));
    }
}
