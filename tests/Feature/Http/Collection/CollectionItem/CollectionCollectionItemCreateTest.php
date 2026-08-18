<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\Http\Collection\CollectionItem;

use App\Models\Collection\Collection;
use App\Models\Wiki\Article;
use Polis\Tests\Application\ApplicationTestCase;
use Polis\Tests\Traits\MocksApplicationLog;

/**
 * Class UserPaymentMethodCreateTest
 */
final class CollectionCollectionItemCreateTest extends ApplicationTestCase
{
    use MocksApplicationLog;

    /**
     * @var string
     */
    private $path = '/v1/collections/';

    private Collection $collection;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();
        $this->mockApplicationLog();

        $this->collection = Collection::factory()->create();

        $this->path .= $this->collection->id.'/items';
    }

    public function test_not_logged_in_user_blocked(): void
    {
        $response = $this->json('POST', $this->path);

        $response->assertStatus(403);
    }

    public function test_incorrect_user_blocked(): void
    {
        $this->actAsUser();
        $response = $this->json('POST', $this->path);

        $response->assertStatus(403);
    }

    public function test_create_successful(): void
    {
        $this->actingAs($this->collection->owner);

        $article = Article::factory()->create();

        $data = [
            'item_type' => 'article',
            'item_id' => $article->id,
            'order' => 4,
        ];
        $response = $this->json('POST', $this->path, $data);

        $response->assertStatus(201);

        $response->assertJson($data);
    }

    public function test_create_fails_required_fields_not_present(): void
    {
        $this->actingAs($this->collection->owner);

        $response = $this->json('POST', $this->path);

        $response->assertStatus(422);

        $response->assertJson([
            'errors' => [
                'item_type' => ['The item type field is required.'],
                'item_id' => ['The item id field is required.'],
                'order' => ['The order field is required.'],
            ],
        ]);
    }

    public function test_create_fails_invalid_enum_fields(): void
    {
        $this->actingAs($this->collection->owner);

        $response = $this->json('POST', $this->path, [
            'item_type' => 'user',
        ]);

        $response->assertStatus(422);

        $response->assertJson([
            'errors' => [
                'item_type' => ['The selected item type is invalid.'],
            ],
        ]);
    }

    public function test_create_fails_invalid_integer_fields(): void
    {
        $this->actingAs($this->collection->owner);

        $response = $this->json('POST', $this->path, [
            'item_id' => 'hello',
            'order' => 'hello',
        ]);

        $response->assertStatus(422);

        $response->assertJson([
            'errors' => [
                'item_id' => ['The item id must be an integer.'],
                'order' => ['The order must be an integer.'],
            ],
        ]);
    }
}
