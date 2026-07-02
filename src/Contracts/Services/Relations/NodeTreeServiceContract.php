<?php

declare(strict_types=1);

namespace Polis\Contracts\Services\Relations;

use Illuminate\Database\Eloquent\Model;
use Polis\Contracts\Models\HasNodeTreeContract;

/**
 * Generic operations over a self-referential node tree, driven by a
 * {@see NodeTreeCodecContract} that supplies all domain-specific knowledge.
 *
 * This is the domain-agnostic extraction of Todo's TodoTaskTreeService: it can
 * hydrate a tree from JSON, serialize it back, re-order siblings and relocate
 * a node (with its whole sub-tree) between scopes/parents — without knowing
 * anything about tallies, hours or todo semantics.
 */
interface NodeTreeServiceContract
{
    /**
     * Replace the entire tree within a scope from a JSON structure.
     * Deletes existing rows in the scope and recreates them from `$rootJson`.
     *
     * @param  mixed  $scopeValue  The scope column value bounding this tree.
     * @param  array<string, mixed>  $rootJson  The root node fragment.
     */
    public function syncFromJson(NodeTreeCodecContract $codec, mixed $scopeValue, array $rootJson): void;

    /**
     * Build the JSON tree (single root) for a scope by reading the relational
     * rows back out and serializing them via the codec.
     *
     * @return array<string, mixed>|null Null when the scope has no root node.
     */
    public function buildTree(NodeTreeCodecContract $codec, mixed $scopeValue): ?array;

    /**
     * Re-number a set of siblings' sort columns to 0,1,2,… preserving order.
     *
     * @param  class-string<Model&HasNodeTreeContract>  $nodeClass
     */
    public function reorderSiblings(string $nodeClass, mixed $scopeValue, ?int $parentId): void;

    /**
     * Move a node (and its entire descendant sub-tree) to a new scope/parent and
     * position, re-ordering the vacated siblings.
     *
     * @param  Model&HasNodeTreeContract  $node
     */
    public function moveNode(Model $node, mixed $targetScopeValue, ?int $targetParentId, int $targetSortOrder): void;
}
