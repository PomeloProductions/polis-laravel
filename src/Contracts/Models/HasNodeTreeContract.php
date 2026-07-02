<?php

declare(strict_types=1);

namespace Polis\Contracts\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Polis\Services\Relations\NodeTreeService;

/**
 * A model that participates in a self-referential ("adjacency list") tree:
 * every node points at an optional parent and may have many ordered children,
 * all within a bounding scope (e.g. all nodes belonging to one component/page).
 *
 * This is the generic contract behind Todo's TodoTaskNode; anything that needs
 * a re-orderable, movable tree of rows (nav trees, outline documents, nested
 * categories) can implement it and reuse {@see NodeTreeService}.
 */
interface HasNodeTreeContract
{
    /**
     * The column that stores the parent node's primary key (nullable for roots).
     * Default convention is `parent_id`.
     */
    public function nodeParentColumn(): string;

    /**
     * The column used to order siblings within the same parent.
     * Default convention is `sort_order`.
     */
    public function nodeSortColumn(): string;

    /**
     * The column that bounds a tree — all nodes sharing a value here belong to
     * the same tree (e.g. `user_page_component_id`). Returning null means the
     * whole table is one tree.
     */
    public function nodeScopeColumn(): ?string;

    /**
     * BelongsTo the parent node.
     */
    public function parent(): BelongsTo;

    /**
     * HasMany child nodes, ordered by the sort column.
     */
    public function children(): HasMany;
}
