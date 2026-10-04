<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\Http\Article;

use App\Models\Role;
use App\Models\Wiki\Article;
use App\Models\Wiki\ArticleIteration;
use App\Models\Wiki\ArticleVersion;
use Polis\Tests\Application\ApplicationTestCase;
use Polis\Tests\Traits\MocksApplicationLog;
use Polis\Tests\Traits\RolesTesting;

/**
 * Class ArticleViewTest
 */
final class ArticleViewTest extends ApplicationTestCase
{
    use MocksApplicationLog, RolesTesting;

    /**
     * @var string
     */
    private $path = '/v1/articles/';

    /**
     * @var Article
     */
    private $article;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();
        $this->mockApplicationLog();

        $this->article = Article::factory()->create();
        $this->path .= $this->article->id;
    }

    public function test_not_logged_in_user_blocked(): void
    {
        $response = $this->json('GET', $this->path);

        $response->assertStatus(403);
    }

    public function test_not_found(): void
    {
        $this->actAsUser();

        $response = $this->json('GET', '/v1/articles/1435');

        $response->assertStatus(404);
    }

    public function test_view_successful(): void
    {
        $this->actAs(Role::ARTICLE_VIEWER);

        $iteration = ArticleIteration::factory()->create([
            'content' => 'hello',
            'article_id' => $this->article->id,
        ]);
        ArticleVersion::factory()->create([
            'article_id' => $this->article->id,
            'article_iteration_id' => $iteration->id,
        ]);

        $response = $this->json('GET', $this->path);

        $response->assertStatus(200);

        $data = $this->article->toArray();
        unset($data['resource']);

        $response->assertJson($data);

        $this->assertNotNull($response->json()['content']);
        $this->assertEquals($this->article->content, $response->json()['content']);
    }
}
