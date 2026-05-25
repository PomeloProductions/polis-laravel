<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\Http\Article\ArticleVersion;

use App\Models\Role;
use App\Models\Wiki\Article;
use App\Models\Wiki\ArticleVersion;
use Polis\Tests\DatabaseSetupTrait;
use Polis\Tests\TestCase;
use Polis\Tests\Traits\MocksApplicationLog;
use Polis\Tests\Traits\RolesTesting;

/**
 * Class ArticleVersionIndexTest
 */
final class ArticleVersionIndexTest extends TestCase
{
    use DatabaseSetupTrait, MocksApplicationLog, RolesTesting;

    /**
     * @var string
     */
    private $path = '/v1/articles/';

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();
        $this->mockApplicationLog();
    }

    public function test_not_found(): void
    {
        $response = $this->json('GET', $this->path.'124/versions');

        $response->assertStatus(404);
    }

    public function test_not_logged_user_blocked(): void
    {
        $article = Article::factory()->create();
        $response = $this->json('GET', $this->path.$article->id.'/versions');

        $response->assertStatus(403);
    }

    public function test_incorrect_user_role_blocked(): void
    {
        foreach ($this->rolesWithoutAdmins([Role::ARTICLE_VIEWER, Role::ARTICLE_EDITOR]) as $role) {
            $this->actAs($role);

            $article = Article::factory()->create();
            $response = $this->json('GET', $this->path.$article->id.'/versions');

            $response->assertStatus(403);
        }
    }

    public function test_get_pagination_empty(): void
    {
        foreach ([Role::ARTICLE_VIEWER, Role::ARTICLE_EDITOR] as $role) {
            $this->actAs($role);
            $article = Article::factory()->create();
            $response = $this->json('GET', $this->path.$article->id.'/versions');

            $response->assertStatus(200);
            $response->assertJson([
                'total' => 0,
                'data' => [],
            ]);
        }
    }

    public function test_get_pagination_result(): void
    {
        $this->actAs(Role::ARTICLE_VIEWER);
        $article = Article::factory()->create();
        ArticleVersion::factory()->count(15)->create([
            'article_id' => $article->id,
        ]);

        ArticleVersion::factory()->count(7)->create();

        // first page
        $response = $this->json('GET', $this->path.$article->id.'/versions');
        $response->assertStatus(200);
        $response->assertJson([
            'total' => 15,
            'current_page' => 1,
            'per_page' => 10,
            'from' => 1,
            'to' => 10,
            'last_page' => 2,
        ])
            ->assertJsonStructure([
                'data' => [
                    '*' => array_keys((new ArticleVersion)->toArray()),
                ],
            ]);

        // second page
        $response = $this->json('GET', $this->path.$article->id.'/versions?page=2');
        $response->assertStatus(200);
        $response->assertJson([
            'total' => 15,
            'current_page' => 2,
            'per_page' => 10,
            'from' => 11,
            'to' => 15,
            'last_page' => 2,
        ])
            ->assertJsonStructure([
                'data' => [
                    '*' => array_keys((new ArticleVersion)->toArray()),
                ],
            ]);

        // page with limit
        $response = $this->json('GET', $this->path.$article->id.'/versions?page=2&limit=5');
        $response->assertStatus(200);
        $response->assertJson([
            'total' => 15,
            'current_page' => 2,
            'per_page' => 5,
            'from' => 6,
            'to' => 10,
            'last_page' => 3,
        ])
            ->assertJsonStructure([
                'data' => [
                    '*' => array_keys((new ArticleVersion)->toArray()),
                ],
            ]);
    }
}
