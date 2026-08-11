<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\Http\Category;

use App\Models\Category;
use Polis\Tests\Application\ApplicationTestCase;
use Polis\Tests\Traits\MocksApplicationLog;

/**
 * Class categoriesViewTest
 */
final class CategoryViewTest extends ApplicationTestCase
{
    use MocksApplicationLog;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();
        $this->mockApplicationLog();
    }

    public function test_get_single_success(): void
    {
        $model = Category::factory()->create([
            'id' => 1,
        ]);

        $response = $this->json('GET', '/v1/categories/1');
        $response->assertJson($model->toArray());
        $response->assertStatus(200);
    }

    public function test_get_single_not_found_fails(): void
    {
        $response = $this->json('GET', '/v1/categories/1')
            ->assertExactJson([
                'message' => 'This item was not found.',
            ]);
        $response->assertStatus(404);
    }

    public function test_get_single_invalid_id_fails(): void
    {
        $response = $this->json('GET', '/v1/categories/a')
            ->assertExactJson([
                'message' => 'This path was not found.',
            ]);
        $response->assertStatus(404);
    }
}
