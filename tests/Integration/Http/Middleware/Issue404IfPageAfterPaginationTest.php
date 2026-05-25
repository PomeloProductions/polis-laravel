<?php

declare(strict_types=1);

namespace Polis\Tests\Integration\Middleware;

use App\Models\Role;
use App\Models\Wiki\Article;
use Polis\Tests\DatabaseSetupTrait;
use Polis\Tests\TestCase;

/**
 * Class Issue404IfPageAfterPaginationTest
 */
final class Issue404IfPageAfterPaginationTest extends TestCase
{
    use DatabaseSetupTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();
    }

    public function test_get_pagination_result(): void
    {
        $this->actAs(Role::ARTICLE_VIEWER);

        Article::factory()->count(3)->create();

        // first page test 200
        $response = $this->json('GET', '/v1/articles?page=1&limit=2');
        $response->assertStatus(200);

        // test second page has 404
        $response = $this->json('GET', '/v1/articles?page=3&limit=2');
        $response->assertStatus(404);
    }
}
