<?php

declare(strict_types=1);

namespace Polis\Tests\Integration\Policies\User;

use App\Models\User\TodoTemplate;
use App\Models\User\User;
use App\Policies\User\TodoTemplatePolicy;
use Polis\Tests\Application\ApplicationTestCase;

final class TodoTemplatePolicyTest extends ApplicationTestCase
{
    
    public function test_all_passes(): void
    {
        $user = User::factory()->create();

        $policy = new TodoTemplatePolicy;

        $this->assertTrue($policy->all($user, $user));
    }

    public function test_all_fails(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $policy = new TodoTemplatePolicy;

        $this->assertFalse($policy->all($user1, $user2));
    }

    public function test_view_passes(): void
    {
        $user = User::factory()->create();
        $template = TodoTemplate::factory()->create([
            'user_id' => $user->id,
        ]);

        $policy = new TodoTemplatePolicy;

        $this->assertTrue($policy->view($user, $user, $template));
    }

    public function test_view_fails_different_logged_in_user(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $template = TodoTemplate::factory()->create([
            'user_id' => $user2->id,
        ]);

        $policy = new TodoTemplatePolicy;

        $this->assertFalse($policy->view($user1, $user2, $template));
    }

    public function test_view_fails_template_not_owned_by_requested_user(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $template = TodoTemplate::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $policy = new TodoTemplatePolicy;

        $this->assertFalse($policy->view($user, $user, $template));
    }

    public function test_create_passes(): void
    {
        $user = User::factory()->create();

        $policy = new TodoTemplatePolicy;

        $this->assertTrue($policy->create($user, $user));
    }

    public function test_create_fails(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $policy = new TodoTemplatePolicy;

        $this->assertFalse($policy->create($user1, $user2));
    }

    public function test_update_passes(): void
    {
        $user = User::factory()->create();
        $template = TodoTemplate::factory()->create([
            'user_id' => $user->id,
        ]);

        $policy = new TodoTemplatePolicy;

        $this->assertTrue($policy->update($user, $user, $template));
    }

    public function test_update_fails_different_logged_in_user(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $template = TodoTemplate::factory()->create([
            'user_id' => $user2->id,
        ]);

        $policy = new TodoTemplatePolicy;

        $this->assertFalse($policy->update($user1, $user2, $template));
    }

    public function test_delete_passes(): void
    {
        $user = User::factory()->create();
        $template = TodoTemplate::factory()->create([
            'user_id' => $user->id,
        ]);

        $policy = new TodoTemplatePolicy;

        $this->assertTrue($policy->delete($user, $user, $template));
    }

    public function test_delete_fails_different_logged_in_user(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $template = TodoTemplate::factory()->create([
            'user_id' => $user2->id,
        ]);

        $policy = new TodoTemplatePolicy;

        $this->assertFalse($policy->delete($user1, $user2, $template));
    }
}
