<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\User\ArticleNote;

use App\Models\User\ArticleNote;
use App\Models\User\User;
use Polis\Tests\Application\ApplicationTestCase;
use Polis\Tests\Traits\MocksApplicationLog;

/**
 * Class UserArticleNoteViewTest
 */
final class UserArticleNoteViewTest extends ApplicationTestCase
{
    use MocksApplicationLog;

    /**
     * @var string
     */
    private $path = '/v1/users/';

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

        $response = $this->json('GET', $this->path.$user->id.'/article-notes/'.$articleNote->id);

        $response->assertStatus(403);
    }

    public function test_different_user_blocked(): void
    {
        $this->actAsUser();
        $otherUser = User::factory()->create();
        $articleNote = ArticleNote::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->json('GET', $this->path.$otherUser->id.'/article-notes/'.$articleNote->id);

        $response->assertStatus(403);
    }

    public function test_user_not_found(): void
    {
        $this->actAsUser();

        $response = $this->json('GET', $this->path.'99999/article-notes/1');

        $response->assertStatus(404);
    }

    public function test_article_note_not_found(): void
    {
        $this->actAsUser();

        $response = $this->json('GET', $this->path.$this->actingAs->id.'/article-notes/99999');

        $response->assertStatus(404);
    }

    public function test_get_single_success(): void
    {
        $this->actAsUser();
        $articleNote = ArticleNote::factory()->create([
            'user_id' => $this->actingAs->id,
            'response' => 'Test response',
        ]);

        $response = $this->json('GET', $this->path.$this->actingAs->id.'/article-notes/'.$articleNote->id);

        $response->assertStatus(200);
        $response->assertJson([
            'id' => $articleNote->id,
            'user_id' => $this->actingAs->id,
            'response' => 'Test response',
        ]);
    }
}
