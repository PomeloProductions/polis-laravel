<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Services\Collection;

use App\Models\Collection\Collection;
use App\Models\Collection\CollectionItem;
use App\Models\Subscription\Subscription;
use App\Models\User\User;
use Polis\Services\Collection\ItemInEntityCollectionService;
use Polis\Tests\TestCase;

final class ItemInEntityCollectionServiceTest extends TestCase
{
    public function test_is_item_in_entity_collection_returns_false_without_collections(): void
    {
        $service = new ItemInEntityCollectionService;

        $user = new User([
            'collections' => collect([]),
        ]);

        $item = new Subscription;

        $result = $service->isItemInEntityCollection($user, $item);

        $this->assertFalse($result);
    }

    public function test_is_item_in_entity_collection_returns_false_without_item_in_collections(): void
    {
        $service = new ItemInEntityCollectionService;

        $user = new User([
            'collections' => collect([
                new Collection([
                    'collectionItems' => collect([
                        new CollectionItem,
                    ]),
                ]),
                new Collection([
                    'collectionItems' => collect([
                        new CollectionItem,
                    ]),
                ]),
            ]),
        ]);

        $item = new Subscription([
            'id' => 23423,
        ]);

        $result = $service->isItemInEntityCollection($user, $item);

        $this->assertFalse($result);
    }

    public function test_is_item_in_entity_collection_returns_false_with_item_type_mismatch(): void
    {
        $service = new ItemInEntityCollectionService;

        $user = new User([
            'collections' => collect([
                new Collection([
                    'collectionItems' => collect([
                        new CollectionItem([
                            'item_type' => 'verification_code',
                            'item_id' => 43,
                        ]),
                    ]),
                ]),
                new Collection([
                    'collectionItems' => collect([
                        new CollectionItem([
                            'item_type' => 'verification_code',
                            'item_id' => 23423,
                        ]),
                    ]),
                ]),
            ]),
        ]);

        $item = new Subscription([
            'id' => 23423,
        ]);

        $result = $service->isItemInEntityCollection($user, $item);

        $this->assertFalse($result);
    }

    public function test_is_item_in_entity_collection_returns_false_with_item_id_mismatch(): void
    {
        $service = new ItemInEntityCollectionService;

        $user = new User([
            'collections' => collect([
                new Collection([
                    'collectionItems' => collect([
                        new CollectionItem([
                            'item_type' => 'verification_code',
                            'item_id' => 43,
                        ]),
                    ]),
                ]),
                new Collection([
                    'collectionItems' => collect([
                        new CollectionItem([
                            'item_type' => 'subscription',
                            'item_id' => 435,
                        ]),
                    ]),
                ]),
            ]),
        ]);

        $item = new Subscription([
            'id' => 23423,
        ]);

        $result = $service->isItemInEntityCollection($user, $item);

        $this->assertFalse($result);
    }

    public function test_is_item_in_entity_collection_returns_true(): void
    {
        $service = new ItemInEntityCollectionService;

        $user = new User([
            'collections' => collect([
                new Collection([
                    'collectionItems' => collect([
                        new CollectionItem([
                            'item_type' => 'verification_code',
                            'item_id' => 43,
                        ]),
                    ]),
                ]),
                new Collection([
                    'collectionItems' => collect([
                        new CollectionItem([
                            'item_type' => 'subscription',
                            'item_id' => 23423,
                        ]),
                    ]),
                ]),
            ]),
        ]);

        $item = new Subscription([
            'id' => 23423,
        ]);

        $result = $service->isItemInEntityCollection($user, $item);

        $this->assertTrue($result);
    }
}
