<?php

declare(strict_types=1);

namespace Polis\Tests\Integration\Policies\User;

use App\Models\User\User;
use App\Models\User\UserPage;
use App\Policies\User\UserPagePolicy;
use Polis\Tests\DatabaseSetupTrait;
use Polis\Tests\TestCase;

final class UserPagePolicyTest extends TestCase
{
    use DatabaseSetupTrait;

    public function test_all_passes(): void
    {
        $user = User::factory()->create();

        $policy = new UserPagePolicy;

        $this->assertTrue($policy->all($user, $user));
    }

    public function test_all_fails(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $policy = new UserPagePolicy;

        $this->assertFalse($policy->all($user1, $user2));
    }

    public function test_view_passes(): void
    {
        $user = User::factory()->create();
        $page = UserPage::factory()->create([
            'user_id' => $user->id,
        ]);

        $policy = new UserPagePolicy;

        $this->assertTrue($policy->view($user, $user, $page));
    }

    public function test_view_fails_different_logged_in_user(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $page = UserPage::factory()->create([
            'user_id' => $user2->id,
        ]);

        $policy = new UserPagePolicy;

        $this->assertFalse($policy->view($user1, $user2, $page));
    }

    public function test_view_fails_page_not_owned_by_requested_user(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $page = UserPage::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $policy = new UserPagePolicy;

        $this->assertFalse($policy->view($user, $user, $page));
    }

    public function test_create_passes(): void
    {
        $user = User::factory()->create();

        $policy = new UserPagePolicy;

        $this->assertTrue($policy->create($user, $user));
    }

    public function test_create_fails(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $policy = new UserPagePolicy;

        $this->assertFalse($policy->create($user1, $user2));
    }

    public function test_update_passes(): void
    {
        $user = User::factory()->create();
        $page = UserPage::factory()->create([
            'user_id' => $user->id,
        ]);

        $policy = new UserPagePolicy;

        $this->assertTrue($policy->update($user, $user, $page));
    }

    public function test_update_fails_different_logged_in_user(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $page = UserPage::factory()->create([
            'user_id' => $user2->id,
        ]);

        $policy = new UserPagePolicy;

        $this->assertFalse($policy->update($user1, $user2, $page));
    }

    public function test_update_fails_page_not_owned_by_requested_user(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $page = UserPage::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $policy = new UserPagePolicy;

        $this->assertFalse($policy->update($user, $user, $page));
    }

    public function test_delete_passes(): void
    {
        $user = User::factory()->create();
        $page = UserPage::factory()->create([
            'user_id' => $user->id,
            'is_required' => false,
        ]);

        $policy = new UserPagePolicy;

        $this->assertTrue($policy->delete($user, $user, $page));
    }

    public function test_delete_fails_different_logged_in_user(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $page = UserPage::factory()->create([
            'user_id' => $user2->id,
            'is_required' => false,
        ]);

        $policy = new UserPagePolicy;

        $this->assertFalse($policy->delete($user1, $user2, $page));
    }

    public function test_delete_fails_page_not_owned_by_requested_user(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $page = UserPage::factory()->create([
            'user_id' => $otherUser->id,
            'is_required' => false,
        ]);

        $policy = new UserPagePolicy;

        $this->assertFalse($policy->delete($user, $user, $page));
    }

    public function test_delete_fails_required_page(): void
    {
        $user = User::factory()->create();
        $page = UserPage::factory()->create([
            'user_id' => $user->id,
            'is_required' => true,
        ]);

        $policy = new UserPagePolicy;

        $this->assertFalse($policy->delete($user, $user, $page));
    }
}
