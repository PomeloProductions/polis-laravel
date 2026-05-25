<?php

declare(strict_types=1);

namespace Polis\Contracts\Services\Relations;

use Illuminate\Database\Eloquent\Collection;
use Polis\Models\BaseModelAbstract;

/**
 * Interface RelationTraversalServiceContract
 */
interface RelationTraversalServiceContract
{
    /**
     * Traverses the relations on a model and returns all related models
     */
    public function traverseRelations(BaseModelAbstract $model, string $relationPath): Collection;
}
