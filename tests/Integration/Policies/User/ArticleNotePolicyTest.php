<?php

declare(strict_types=1);

namespace Polis\Tests\Integration\Policies\User;

use App\Models\User\ArticleNote;
use App\Models\User\User;
use App\Policies\User\ArticleNotePolicy;
use Polis\Tests\Application\ApplicationTestCase;

/**
 * Class ArticleNotePolicyTest
 */
final class ArticleNotePolicyTest extends ApplicationTestCase
{
    
    public function test_all_passes(): void
    {
        $user = User::factory()->create();

        $policy = new ArticleNotePolicy;

        $this->assertTrue($policy->all($user, $user));
    }

    public function test_all_fails(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $policy = new ArticleNotePolicy;

        $this->assertFalse($policy->all($user1, $user2));
    }

    public function test_create_passes(): void
    {
        $user = User::factory()->create();

        $policy = new ArticleNotePolicy;

        $this->assertTrue($policy->create($user, $user));
    }

    public function test_create_fails(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $policy = new ArticleNotePolicy;

        $this->assertFalse($policy->create($user1, $user2));
    }

    public function test_view_passes(): void
    {
        $user = User::factory()->create();

        $policy = new ArticleNotePolicy;

        $articleNote = ArticleNote::factory()->create([
            'user_id' => $user->id,
        ]);
        $this->assertTrue($policy->view($user, $user, $articleNote));
    }

    public function test_view_fails(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $articleNote = ArticleNote::factory()->create([
            'user_id' => $user2->id,
        ]);

        $policy = new ArticleNotePolicy;

        $this->assertFalse($policy->view($user1, $user2, $articleNote));
        $this->assertFalse($policy->view($user1, $user1, $articleNote));
    }

    public function test_update_passes(): void
    {
        $user = User::factory()->create();

        $policy = new ArticleNotePolicy;

        $articleNote = ArticleNote::factory()->create([
            'user_id' => $user->id,
        ]);
        $this->assertTrue($policy->update($user, $user, $articleNote));
    }

    public function test_update_fails(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $articleNote = ArticleNote::factory()->create([
            'user_id' => $user2->id,
        ]);

        $policy = new ArticleNotePolicy;

        $this->assertFalse($policy->update($user1, $user2, $articleNote));
        $this->assertFalse($policy->update($user1, $user1, $articleNote));
    }

    public function test_delete_passes(): void
    {
        $user = User::factory()->create();

        $policy = new ArticleNotePolicy;

        $articleNote = ArticleNote::factory()->create([
            'user_id' => $user->id,
        ]);
        $this->assertTrue($policy->delete($user, $user, $articleNote));
    }

    public function test_delete_fails(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $articleNote = ArticleNote::factory()->create([
            'user_id' => $user2->id,
        ]);

        $policy = new ArticleNotePolicy;

        $this->assertFalse($policy->delete($user1, $user2, $articleNote));
        $this->assertFalse($policy->delete($user1, $user1, $articleNote));
    }
}
