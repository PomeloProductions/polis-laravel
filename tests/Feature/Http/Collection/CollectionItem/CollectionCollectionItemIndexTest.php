<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\Http\Collection\CollectionItem;

use App\Models\Collection\Collection;
use App\Models\Collection\CollectionItem;
use Polis\Tests\Application\ApplicationTestCase;
use Polis\Tests\Traits\MocksApplicationLog;

/**
 * Class UserSubscriptionIndexTest
 */
final class CollectionCollectionItemIndexTest extends ApplicationTestCase
{
    use MocksApplicationLog;

    /**
     * @var string
     */
    private $path = '/v1/collections/';

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();
        $this->mockApplicationLog();
    }

    public function test_not_logged_in_user_blocked(): void
    {
        $collection = Collection::factory()->create();

        $response = $this->json('GET', $this->path.$collection->id.'/items');

        $response->assertStatus(403);
    }

    public function test_not_found(): void
    {
        $this->actAsUser();

        $response = $this->json('GET', $this->path.'12/items');

        $response->assertStatus(404);
    }

    public function test_non_public_collection_blocked(): void
    {
        $this->actAsUser();
        $collection = Collection::factory()->create([
            'is_public' => false,
        ]);

        $response = $this->json('GET', $this->path.$collection->id.'/items');

        $response->assertStatus(403);
    }

    public function test_get_pagination_empty(): void
    {
        $this->actAsUser();
        $collection = Collection::factory()->create([
            'is_public' => true,
        ]);

        $response = $this->json('GET', $this->path.$collection->id.'/items');

        $response->assertStatus(200);
        $response->assertJson([
            'total' => 0,
            'data' => [],
        ]);
    }

    public function test_get_pagination_result(): void
    {
        $this->actAsUser();
        $collection = Collection::factory()->create([
            'is_public' => true,
        ]);

        CollectionItem::factory()->count(6)->create();
        CollectionItem::factory()->count(15)->create([
            'collection_id' => $collection->id,
        ]);

        // first page

        $response = $this->json('GET', $this->path.$collection->id.'/items');
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
                    '*' => array_keys((new CollectionItem)->toArray()),
                ],
            ]);

        // second page
        $response = $this->json('GET', $this->path.$collection->id.'/items?page=2');
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
                    '*' => array_keys((new CollectionItem)->toArray()),
                ],
            ]);

        // page with limit
        $response = $this->json('GET', $this->path.$collection->id.'/items?page=2&limit=5');
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
                    '*' => array_keys((new CollectionItem)->toArray()),
                ],
            ]);
    }
}
