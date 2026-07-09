<?php

declare(strict_types=1);

namespace Polis\Contracts\Services\Relations;

use Illuminate\Database\Eloquent\Model;
use Polis\Contracts\Models\HasNodeTreeContract;
use Polis\Services\Relations\NodeTreeService;

/**
 * Translates between a node model and its JSON representation for a specific
 * domain. {@see NodeTreeService} owns the generic
 * tree mechanics (walking, scoping, ordering, moving); the codec owns all
 * domain-specific column knowledge (which attributes to persist, what nested
 * side-relations a node carries, how to name a node in JSON).
 *
 * A codec makes the node tree service reusable: Todo supplies a codec that
 * knows about tally/schedule/groups/sub_items; a different consumer supplies
 * a codec that knows about its own columns — the service is unchanged.
 *
 * @template TNode of Model&HasNodeTreeContract
 */
interface NodeTreeCodecContract
{
    /**
     * The node model class this codec handles.
     *
     * @return class-string<TNode>
     */
    public function nodeClass(): string;

    /**
     * The JSON key under which a node's ordered children live.
     * Convention: "children".
     */
    public function childrenKey(): string;

    /**
     * Build the attribute array used to CREATE a node row from a JSON fragment.
     * The service supplies the scope value, parent id and sort order; the codec
     * fills in every domain-specific column. Structural keys (children and any
     * nested side-relations) should be omitted from the returned attributes.
     *
     * @param  array<string, mixed>  $data  The JSON fragment for this node.
     * @param  array{scope: mixed, parent_id: int|null, sort_order: int}  $position
     * @return array<string, mixed>
     */
    public function attributesFromJson(array $data, array $position): array;

    /**
     * Persist any non-child side-relations a node carries (e.g. Todo's rotating
     * groups, sub-items, calendar pivots) after the node row itself is created.
     * The default (Todo-agnostic) case is a no-op.
     *
     * @param  TNode  $node  The freshly created node model.
     * @param  array<string, mixed>  $data  The JSON fragment for this node.
     */
    public function syncSideRelations(Model $node, array $data): void;

    /**
     * Serialize a single node model (already loaded with its relations) to the
     * JSON structure the domain expects. Implementations recurse into children
     * via the callback so the service stays in control of ordering/scoping.
     *
     * @param  TNode  $node
     * @param  callable(TNode): array<string, mixed>  $serializeChild
     * @return array<string, mixed>
     */
    public function nodeToJson(Model $node, callable $serializeChild): array;

    /**
     * The eager-load spec used when reading a tree back out of the database.
     * Return an array suitable for `Model::with(...)`. Callers merge in the
     * recursive `children` load automatically.
     *
     * @return array<int|string, mixed>
     */
    public function eagerLoad(): array;
}
