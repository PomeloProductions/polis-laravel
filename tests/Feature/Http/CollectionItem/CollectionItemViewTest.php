<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\V1\CollectionItem;

use App\Models\Collection\Collection;
use App\Models\Collection\CollectionItem;
use Polis\Tests\DatabaseSetupTrait;
use Polis\Tests\TestCase;
use Polis\Tests\Traits\MocksApplicationLog;

/**
 * Class categoriesViewTest
 */
final class CollectionItemViewTest extends TestCase
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
        CollectionItem::factory()->create([
            'id' => 1,
        ]);
        $response = $this->json('GET', '/v1/collection-items/1');
        $response->assertStatus(403);
    }

    public function test_get_single_not_found_fails(): void
    {
        $this->actAsUser();
        $response = $this->json('GET', '/v1/collection-items/1');
        $response->assertExactJson([
            'message' => 'This item was not found.',
        ]);
        $response->assertStatus(404);
    }

    public function test_get_single_invalid_id_fails(): void
    {
        $this->actAsUser();
        $response = $this->json('GET', '/v1/collection-items/a')
            ->assertExactJson([
                'message' => 'This path was not found.',
            ]);
        $response->assertStatus(404);
    }

    public function test_get_single_non_public_blocked(): void
    {
        $this->actAsUser();
        CollectionItem::factory()->create([
            'id' => 1,
            'collection_id' => Collection::factory()->create([
                'is_public' => false,
            ])->id,
        ]);

        $response = $this->json('GET', '/v1/collection-items/1');
        $response->assertStatus(403);
    }

    public function test_get_single_success(): void
    {
        $this->actAsUser();
        $model = CollectionItem::factory()->create([
            'id' => 1,
            'collection_id' => Collection::factory()->create([
                'is_public' => true,
            ])->id,
        ]);

        $response = $this->json('GET', '/v1/collection-items/1');
        $response->assertStatus(200);
        $response->assertJson($model->toArray());
    }

    public function test_get_single_non_public_success(): void
    {
        $this->actAsUser();
        $model = CollectionItem::factory()->create([
            'id' => 1,
            'collection_id' => Collection::factory()->create([
                'is_public' => false,
                'owner_id' => $this->actingAs->id,
            ])->id,
        ]);

        $response = $this->json('GET', '/v1/collection-items/1');
        $response->assertStatus(200);
        $response->assertJson($model->toArray());
    }
}
