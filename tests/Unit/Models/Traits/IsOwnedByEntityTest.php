<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Models\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Polis\Models\Traits\IsOwnedByEntity;
use Polis\Tests\TestCase;

/**
 * Coverage for the IsOwnedByEntity trait — the "belongs to an entity owner"
 * side of the entity generalization. It provides the single canonical
 * owner(): MorphTo relation over the polymorphic owner_id/owner_type columns,
 * so a resource resolves its owning entity (User / Organization / any future
 * entity type) generically.
 */
final class IsOwnedByEntityTest extends TestCase
{
    public function test_owner_returns_a_polymorphic_morph_to_relation(): void
    {
        $model = new class extends Model
        {
            use IsOwnedByEntity;

            protected $table = 'owned_things';
        };

        $relation = $model->owner();

        $this->assertInstanceOf(MorphTo::class, $relation);
        // The morphTo is keyed off the conventional owner_id/owner_type columns.
        $this->assertSame('owner_type', $relation->getMorphType());
        $this->assertSame('owner_id', $relation->getForeignKeyName());
    }
}
