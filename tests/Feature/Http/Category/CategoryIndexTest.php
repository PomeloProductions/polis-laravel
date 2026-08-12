<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\Http\Category;

use App\Models\Category;
use Polis\Tests\Application\ApplicationTestCase;
use Polis\Tests\Traits\MocksApplicationLog;

/**
 * Class CategoryIndexTest
 */
final class CategoryIndexTest extends ApplicationTestCase
{
    use MocksApplicationLog;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();
        $this->mockApplicationLog();
    }

    public function test_get_pagination_empty(): void
    {
        $response = $this->json('GET', '/v1/categories');

        $response->assertStatus(200);
        $response->assertJson([
            'total' => 0,
            'data' => [],
        ]);
    }

    public function test_get_pagination_result(): void
    {
        Category::factory()->count(15)->create();

        // first page
        $response = $this->json('GET', '/v1/categories');
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
                    '*' => array_keys((new Category)->toArray()),
                ],
            ]);
        $response->assertStatus(200);

        // second page
        $response = $this->json('GET', '/v1/categories?page=2');
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
                    '*' => array_keys((new Category)->toArray()),
                ],
            ]);
        $response->assertStatus(200);

        // page with limit
        $response = $this->json('GET', '/v1/categories?page=2&limit=5');
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
                    '*' => array_keys((new Category)->toArray()),
                ],
            ]);
        $response->assertStatus(200);
    }
}
