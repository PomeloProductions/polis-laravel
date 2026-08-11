<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\User\ArticleNote;

use App\Models\User\ArticleNote;
use App\Models\User\User;
use Polis\Tests\Application\ApplicationTestCase;
use Polis\Tests\Traits\MocksApplicationLog;

/**
 * Class UserArticleNoteDeleteTest
 */
final class UserArticleNoteDeleteTest extends ApplicationTestCase
{
    use MocksApplicationLog;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();
        $this->mockApplicationLog();
    }

    public function test_not_logged_in_user_blocked(): void
    {
        $user = User::factory()->create();
        $articleNote = ArticleNote::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->json('DELETE', '/v1/users/'.$user->id.'/article-notes/'.$articleNote->id);
        $response->assertStatus(403);
    }

    public function test_different_user_blocked(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $this->actingAs($otherUser);

        $articleNote = ArticleNote::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->json('DELETE', '/v1/users/'.$user->id.'/article-notes/'.$articleNote->id);
        $response->assertStatus(403);
    }

    public function test_delete_single(): void
    {
        $this->actAsUser();

        $articleNote = ArticleNote::factory()->create([
            'user_id' => $this->actingAs->id,
        ]);

        $response = $this->json('DELETE', '/v1/users/'.$this->actingAs->id.'/article-notes/'.$articleNote->id);

        $response->assertStatus(204);
        $this->assertNull(ArticleNote::find($articleNote->id));
    }

    public function test_delete_single_invalid_id_fails(): void
    {
        $this->actAsUser();

        $response = $this->json('DELETE', '/v1/users/'.$this->actingAs->id.'/article-notes/a')
            ->assertExactJson([
                'message' => 'This item was not found.',
            ]);
        $response->assertStatus(404);
    }

    public function test_delete_single_not_found_fails(): void
    {
        $this->actAsUser();

        $response = $this->json('DELETE', '/v1/users/'.$this->actingAs->id.'/article-notes/99999')
            ->assertExactJson([
                'message' => 'This item was not found.',
            ]);
        $response->assertStatus(404);
    }
}
