<?php

declare(strict_types=1);

namespace Polis\Models\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Polis\Contracts\Models\HasNodeTreeContract;

/**
 * Default implementation of {@see HasNodeTreeContract}
 * for an Eloquent model that stores a self-referential tree in an adjacency
 * list (parent_id / sort_order columns, optionally bounded by a scope column).
 *
 * Consumers can override any of the `node*Column()` methods (or the relations)
 * if their schema uses different column names.
 *
 * @mixin Model
 */
trait HasNodeTree
{
    public function nodeParentColumn(): string
    {
        return 'parent_id';
    }

    public function nodeSortColumn(): string
    {
        return 'sort_order';
    }

    public function nodeScopeColumn(): ?string
    {
        return null;
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(static::class, $this->nodeParentColumn());
    }

    public function children(): HasMany
    {
        return $this->hasMany(static::class, $this->nodeParentColumn())
            ->orderBy($this->nodeSortColumn());
    }
}
