<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Services\Relations;

use App\Models\Collection\Collection as CollectionModel;
use App\Models\Collection\CollectionItem;
use App\Models\User\User;
use App\Models\Wiki\Article;
use App\Models\Wiki\ArticleIteration;
use Illuminate\Database\Eloquent\Collection;
use Polis\Services\Relations\RelationTraversalService;
use Polis\Tests\TestCase;

/**
 * Class RelationTraversalServiceTest
 */
class RelationTraversalServiceTest extends TestCase
{
    private RelationTraversalService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new RelationTraversalService;
    }

    public function test_traverse_relations_with_empty_path()
    {
        $collection = new CollectionModel;
        $collection->id = 1;

        $result = $this->service->traverseRelations($collection, '');

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertEquals(1, $result->count());
        $this->assertSame($collection, $result->first());
    }

    public function test_traverse_relations_with_single_relation()
    {
        $collection = new CollectionModel;
        $collection->id = 1;

        $collectionItem = new CollectionItem;
        $collectionItem->id = 2;
        $collectionItem->collection_id = $collection->id;

        $collection->setRelation('items', new Collection([$collectionItem]));

        $result = $this->service->traverseRelations($collection, 'items');

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertEquals(1, $result->count());
        $this->assertSame($collectionItem, $result->first());
    }

    public function test_traverse_relations_with_nested_relations()
    {
        $collection = new CollectionModel;
        $collection->id = 1;

        $parentItem = new CollectionItem;
        $parentItem->id = 2;
        $parentItem->collection_id = $collection->id;

        $childItem = new CollectionItem;
        $childItem->id = 3;
        $childItem->collection_id = $collection->id;

        $parentItem->setRelation('children', new Collection([$childItem]));
        $collection->setRelation('items', new Collection([$parentItem]));

        $result = $this->service->traverseRelations($collection, 'items.children');

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertEquals(1, $result->count());
        $this->assertSame($childItem, $result->first());
    }

    public function test_traverse_relations_with_mixed_relation_types()
    {
        $collection = new CollectionModel;
        $collection->id = 1;

        $collectionItem1 = new CollectionItem;
        $collectionItem1->id = 2;
        $collectionItem1->collection_id = $collection->id;

        $collectionItem2 = new CollectionItem;
        $collectionItem2->id = 3;
        $collectionItem2->collection_id = $collection->id;

        $collection->setRelation('items', new Collection([$collectionItem1, $collectionItem2]));

        $result = $this->service->traverseRelations($collection, 'items');

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertEquals(2, $result->count());
        $this->assertSame($collectionItem1, $result->first());
        $this->assertSame($collectionItem2, $result->last());
    }

    public function test_traverse_relations_with_preloaded_relations()
    {
        $collection = new CollectionModel;
        $collection->id = 1;

        $collectionItem = new CollectionItem;
        $collectionItem->id = 2;
        $collectionItem->collection_id = $collection->id;

        $collection->setRelation('items', new Collection([$collectionItem]));

        $result = $this->service->traverseRelations($collection, 'items');

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertEquals(1, $result->count());
        $this->assertSame($collectionItem, $result->first());
    }

    public function test_traverse_relations_with_three_level_nesting()
    {
        // Create the initial user
        $user = new User;
        $user->id = 1;

        // Create an article created by the user
        $article = new Article;
        $article->id = 2;
        $article->created_by_id = $user->id;

        // Create an iteration of the article
        $iteration = new ArticleIteration;
        $iteration->id = 3;
        $iteration->article_id = $article->id;
        $iteration->created_by_id = 4; // Different user created the iteration

        // Create the user who created the iteration
        $iterationCreator = new User;
        $iterationCreator->id = 4;

        // Set up the relations
        $user->setRelation('createdArticles', new Collection([$article]));
        $article->setRelation('iterations', new Collection([$iteration]));
        $iteration->setRelation('createdBy', $iterationCreator);

        $result = $this->service->traverseRelations($user, 'createdArticles.iterations.createdBy');

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertEquals(1, $result->count());
        $this->assertSame($iterationCreator, $result->first());
    }
}
