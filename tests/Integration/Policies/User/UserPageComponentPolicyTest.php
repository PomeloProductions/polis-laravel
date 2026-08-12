<?php

declare(strict_types=1);

namespace Polis\Tests\Integration\Policies\User;

use App\Models\User\User;
use App\Models\User\UserPage;
use App\Models\User\UserPageComponent;
use App\Policies\User\UserPageComponentPolicy;
use Polis\Tests\Application\ApplicationTestCase;

final class UserPageComponentPolicyTest extends ApplicationTestCase
{
    
    public function test_all_passes(): void
    {
        $user = User::factory()->create();
        $page = UserPage::factory()->create([
            'user_id' => $user->id,
        ]);

        $policy = new UserPageComponentPolicy;

        $this->assertTrue($policy->all($user, $user, $page));
    }

    public function test_all_fails_different_logged_in_user(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $page = UserPage::factory()->create([
            'user_id' => $user2->id,
        ]);

        $policy = new UserPageComponentPolicy;

        $this->assertFalse($policy->all($user1, $user2, $page));
    }

    public function test_all_fails_page_not_owned_by_requested_user(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $page = UserPage::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $policy = new UserPageComponentPolicy;

        $this->assertFalse($policy->all($user, $user, $page));
    }

    public function test_create_passes(): void
    {
        $user = User::factory()->create();
        $page = UserPage::factory()->create([
            'user_id' => $user->id,
        ]);

        $policy = new UserPageComponentPolicy;

        $this->assertTrue($policy->create($user, $user, $page));
    }

    public function test_create_fails_different_logged_in_user(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $page = UserPage::factory()->create([
            'user_id' => $user2->id,
        ]);

        $policy = new UserPageComponentPolicy;

        $this->assertFalse($policy->create($user1, $user2, $page));
    }

    public function test_create_fails_page_not_owned_by_requested_user(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $page = UserPage::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $policy = new UserPageComponentPolicy;

        $this->assertFalse($policy->create($user, $user, $page));
    }

    public function test_update_passes(): void
    {
        $user = User::factory()->create();
        $page = UserPage::factory()->create([
            'user_id' => $user->id,
        ]);
        $component = UserPageComponent::factory()->create([
            'user_page_id' => $page->id,
        ]);

        $policy = new UserPageComponentPolicy;

        $this->assertTrue($policy->update($user, $user, $page, $component));
    }

    public function test_update_fails_different_logged_in_user(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $page = UserPage::factory()->create([
            'user_id' => $user2->id,
        ]);
        $component = UserPageComponent::factory()->create([
            'user_page_id' => $page->id,
        ]);

        $policy = new UserPageComponentPolicy;

        $this->assertFalse($policy->update($user1, $user2, $page, $component));
    }

    public function test_update_fails_page_not_owned_by_requested_user(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $page = UserPage::factory()->create([
            'user_id' => $otherUser->id,
        ]);
        $component = UserPageComponent::factory()->create([
            'user_page_id' => $page->id,
        ]);

        $policy = new UserPageComponentPolicy;

        $this->assertFalse($policy->update($user, $user, $page, $component));
    }

    public function test_update_fails_component_not_on_page(): void
    {
        $user = User::factory()->create();
        $page = UserPage::factory()->create([
            'user_id' => $user->id,
        ]);
        $otherPage = UserPage::factory()->create([
            'user_id' => $user->id,
        ]);
        $component = UserPageComponent::factory()->create([
            'user_page_id' => $otherPage->id,
        ]);

        $policy = new UserPageComponentPolicy;

        $this->assertFalse($policy->update($user, $user, $page, $component));
    }

    public function test_delete_passes(): void
    {
        $user = User::factory()->create();
        $page = UserPage::factory()->create([
            'user_id' => $user->id,
        ]);
        $component = UserPageComponent::factory()->create([
            'user_page_id' => $page->id,
        ]);

        $policy = new UserPageComponentPolicy;

        $this->assertTrue($policy->delete($user, $user, $page, $component));
    }

    public function test_delete_fails_different_logged_in_user(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $page = UserPage::factory()->create([
            'user_id' => $user2->id,
        ]);
        $component = UserPageComponent::factory()->create([
            'user_page_id' => $page->id,
        ]);

        $policy = new UserPageComponentPolicy;

        $this->assertFalse($policy->delete($user1, $user2, $page, $component));
    }

    public function test_delete_fails_page_not_owned_by_requested_user(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $page = UserPage::factory()->create([
            'user_id' => $otherUser->id,
        ]);
        $component = UserPageComponent::factory()->create([
            'user_page_id' => $page->id,
        ]);

        $policy = new UserPageComponentPolicy;

        $this->assertFalse($policy->delete($user, $user, $page, $component));
    }

    public function test_delete_fails_component_not_on_page(): void
    {
        $user = User::factory()->create();
        $page = UserPage::factory()->create([
            'user_id' => $user->id,
        ]);
        $otherPage = UserPage::factory()->create([
            'user_id' => $user->id,
        ]);
        $component = UserPageComponent::factory()->create([
            'user_page_id' => $otherPage->id,
        ]);

        $policy = new UserPageComponentPolicy;

        $this->assertFalse($policy->delete($user, $user, $page, $component));
    }
}
