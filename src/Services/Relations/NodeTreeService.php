<?php

declare(strict_types=1);

namespace Polis\Services\Relations;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Polis\Contracts\Models\HasNodeTreeContract;
use Polis\Contracts\Services\Relations\NodeTreeCodecContract;
use Polis\Contracts\Services\Relations\NodeTreeServiceContract;

/**
 * Generic self-referential tree engine — the domain-agnostic form of Todo's
 * TodoTaskTreeService. All domain knowledge (columns, side-relations, JSON
 * shape) is supplied by a {@see NodeTreeCodecContract}; this class owns the
 * mechanics of walking, scoping, ordering and relocating nodes.
 */
class NodeTreeService implements NodeTreeServiceContract
{
    /**
     * Maximum recursion depth when hydrating/serializing a tree. Guards against
     * pathological / cyclic data.
     */
    protected int $maxDepth = 25;

    public function syncFromJson(NodeTreeCodecContract $codec, mixed $scopeValue, array $rootJson): void
    {
        $nodeClass = $codec->nodeClass();
        $prototype = new $nodeClass;
        $scopeColumn = $prototype->nodeScopeColumn();

        DB::transaction(function () use ($codec, $scopeValue, $rootJson, $nodeClass, $scopeColumn): void {
            $query = $nodeClass::query();
            if ($scopeColumn !== null) {
                $query->where($scopeColumn, $scopeValue);
            }
            $query->get()->each(function (Model $node): void {
                $node->forceDelete();
            });

            $this->createNodeFromJson($codec, $scopeValue, null, $rootJson, 0, 0);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function createNodeFromJson(
        NodeTreeCodecContract $codec,
        mixed $scopeValue,
        ?int $parentId,
        array $data,
        int $sortOrder,
        int $depth,
    ): Model {
        if ($depth > $this->maxDepth) {
            throw new \RuntimeException('Node tree exceeded maximum depth of '.$this->maxDepth.'.');
        }

        $nodeClass = $codec->nodeClass();

        $attributes = $codec->attributesFromJson($data, [
            'scope' => $scopeValue,
            'parent_id' => $parentId,
            'sort_order' => $sortOrder,
        ]);

        /** @var Model&HasNodeTreeContract $node */
        $node = $nodeClass::create($attributes);

        $codec->syncSideRelations($node, $data);

        $childrenKey = $codec->childrenKey();
        if (! empty($data[$childrenKey]) && is_array($data[$childrenKey])) {
            foreach (array_values($data[$childrenKey]) as $idx => $child) {
                if (is_array($child)) {
                    $this->createNodeFromJson($codec, $scopeValue, $node->getKey(), $child, $idx, $depth + 1);
                }
            }
        }

        return $node;
    }

    public function buildTree(NodeTreeCodecContract $codec, mixed $scopeValue): ?array
    {
        $nodeClass = $codec->nodeClass();
        $prototype = new $nodeClass;
        $scopeColumn = $prototype->nodeScopeColumn();
        $parentColumn = $prototype->nodeParentColumn();
        $sortColumn = $prototype->nodeSortColumn();

        $query = $nodeClass::query()->whereNull($parentColumn);
        if ($scopeColumn !== null) {
            $query->where($scopeColumn, $scopeValue);
        }

        $rootNode = $query->orderBy($sortColumn)
            ->with($this->recursiveLoad($codec, $parentColumn, $sortColumn))
            ->first();

        if (! $rootNode) {
            return null;
        }

        return $this->serializeNode($codec, $rootNode);
    }

    /**
     * Build a recursive `with()` spec: children (ordered) plus the codec's own
     * eager-load spec, nested to a bounded depth.
     *
     * @return array<int|string, mixed>
     */
    protected function recursiveLoad(NodeTreeCodecContract $codec, string $parentColumn, string $sortColumn, int $depth = 8): array
    {
        if ($depth <= 0) {
            return $codec->eagerLoad();
        }

        return array_merge($codec->eagerLoad(), [
            'children' => function ($q) use ($codec, $parentColumn, $sortColumn, $depth): void {
                $q->orderBy($sortColumn)->with($this->recursiveLoad($codec, $parentColumn, $sortColumn, $depth - 1));
            },
        ]);
    }

    /**
     * @param  Model&HasNodeTreeContract  $node
     * @return array<string, mixed>
     */
    protected function serializeNode(NodeTreeCodecContract $codec, Model $node): array
    {
        return $codec->nodeToJson($node, fn (Model $child): array => $this->serializeNode($codec, $child));
    }

    public function reorderSiblings(string $nodeClass, mixed $scopeValue, ?int $parentId): void
    {
        /** @var Model&HasNodeTreeContract $prototype */
        $prototype = new $nodeClass;
        $scopeColumn = $prototype->nodeScopeColumn();
        $parentColumn = $prototype->nodeParentColumn();
        $sortColumn = $prototype->nodeSortColumn();

        $query = $nodeClass::query()->where($parentColumn, $parentId);
        if ($scopeColumn !== null) {
            $query->where($scopeColumn, $scopeValue);
        }

        $siblings = $query->orderBy($sortColumn)->get();

        foreach ($siblings->values() as $idx => $sibling) {
            if ((int) $sibling->{$sortColumn} !== $idx) {
                $sibling->forceFill([$sortColumn => $idx])->saveQuietly();
            }
        }
    }

    public function moveNode(Model $node, mixed $targetScopeValue, ?int $targetParentId, int $targetSortOrder): void
    {
        /** @var Model&HasNodeTreeContract $node */
        $scopeColumn = $node->nodeScopeColumn();
        $parentColumn = $node->nodeParentColumn();
        $sortColumn = $node->nodeSortColumn();

        DB::transaction(function () use ($node, $targetScopeValue, $targetParentId, $targetSortOrder, $scopeColumn, $parentColumn, $sortColumn): void {
            $originalScope = $scopeColumn !== null ? $node->{$scopeColumn} : null;
            $originalParent = $node->{$parentColumn};

            $update = [
                $parentColumn => $targetParentId,
                $sortColumn => $targetSortOrder,
            ];
            if ($scopeColumn !== null) {
                $update[$scopeColumn] = $targetScopeValue;
            }
            $node->forceFill($update)->save();

            // Re-scope all descendants so the whole sub-tree follows the move.
            if ($scopeColumn !== null && $originalScope != $targetScopeValue) {
                $this->rescopeDescendants($node, $scopeColumn, $parentColumn, $targetScopeValue);
            }

            // Renumber the siblings the node left behind.
            $this->reorderSiblings($node::class, $originalScope, $originalParent !== null ? (int) $originalParent : null);
        });
    }

    /**
     * @param  Model&HasNodeTreeContract  $node
     */
    protected function rescopeDescendants(Model $node, string $scopeColumn, string $parentColumn, mixed $targetScopeValue): void
    {
        $children = $node::query()->where($parentColumn, $node->getKey())->get();
        foreach ($children as $child) {
            $child->forceFill([$scopeColumn => $targetScopeValue])->saveQuietly();
            $this->rescopeDescendants($child, $scopeColumn, $parentColumn, $targetScopeValue);
        }
    }
}
