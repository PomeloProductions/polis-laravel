<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Models\Traits;

use Illuminate\Database\Eloquent\Model;
use Polis\Contracts\Models\HasNodeTreeContract;
use Polis\Models\Traits\HasNodeTree;
use Polis\Tests\TestCase;

final class HasNodeTreeTest extends TestCase
{
    public function test_default_column_conventions(): void
    {
        $model = new HasNodeTreeStubModel;

        $this->assertEquals('parent_id', $model->nodeParentColumn());
        $this->assertEquals('sort_order', $model->nodeSortColumn());
        $this->assertNull($model->nodeScopeColumn());
    }

    public function test_parent_and_children_relations_use_the_parent_column(): void
    {
        $model = new HasNodeTreeStubModel;

        $this->assertEquals('node_stubs.parent_id', $model->parent()->getQualifiedForeignKeyName());
        $this->assertEquals('node_stubs.parent_id', $model->children()->getQualifiedForeignKeyName());
    }

    public function test_children_is_ordered_by_the_sort_column(): void
    {
        $orders = new HasNodeTreeStubModel;
        $query = $orders->children()->getQuery();

        $found = collect($query->getQuery()->orders ?? [])
            ->contains(fn ($o) => ($o['column'] ?? null) === 'sort_order');

        $this->assertTrue($found, 'children() should order by the node sort column');
    }
}

/**
 * Minimal in-memory model exercising the trait's defaults with no DB.
 */
class HasNodeTreeStubModel extends Model implements HasNodeTreeContract
{
    use HasNodeTree;

    protected $table = 'node_stubs';
}
