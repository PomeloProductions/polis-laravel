<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\Http\Collection;

use App\Models\Collection\Collection;
use Polis\Tests\DatabaseSetupTrait;
use Polis\Tests\TestCase;
use Polis\Tests\Traits\MocksApplicationLog;

/**
 * Class categoriesViewTest
 */
final class CollectionViewTest extends TestCase
{
    use DatabaseSetupTrait, MocksApplicationLog;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();
        $this->mockApplicationLog();
    }

    public function test_get_blocks_not_logged_in(): void
    {
        Collection::factory()->create([
            'id' => 1,
            'is_public' => false,
        ]);
        $response = $this->json('GET', '/v1/collections/1');
        $response->assertStatus(403);
    }

    public function test_get_single_not_found_fails(): void
    {
        $this->actAsUser();
        $response = $this->json('GET', '/v1/collections/1');
        $response->assertExactJson([
            'message' => 'This item was not found.',
        ]);
        $response->assertStatus(404);
    }

    public function test_get_single_invalid_id_fails(): void
    {
        $this->actAsUser();
        $response = $this->json('GET', '/v1/collections/a')
            ->assertExactJson([
                'message' => 'This path was not found.',
            ]);
        $response->assertStatus(404);
    }

    public function test_get_single_non_public_blocked(): void
    {
        $this->actAsUser();
        Collection::factory()->create([
            'id' => 1,
            'is_public' => false,
        ]);

        $response = $this->json('GET', '/v1/collections/1');
        $response->assertStatus(403);
    }

    public function test_get_single_success(): void
    {
        $this->actAsUser();
        $model = Collection::factory()->create([
            'id' => 1,
            'is_public' => true,
        ]);

        $response = $this->json('GET', '/v1/collections/1');
        $response->assertStatus(200);
        $response->assertJson($model->toArray());
    }

    public function test_get_single_non_public_success(): void
    {
        $this->actAsUser();
        $model = Collection::factory()->create([
            'id' => 1,
            'owner_id' => $this->actingAs->id,
            'is_public' => false,
        ]);

        $response = $this->json('GET', '/v1/collections/1');
        $response->assertStatus(200);
        $response->assertJson($model->toArray());
    }
}
