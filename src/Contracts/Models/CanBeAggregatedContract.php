<?php

declare(strict_types=1);

namespace Polis\Contracts\Models;

/**
 * Interface CanBeAggregatedContract
 */
interface CanBeAggregatedContract
{
    /**
     * Returns the relation paths to the models that can be target statistics
     * For example: ["collectionItem.collection"] would mean this model affects statistics on collections
     * through the collectionItem relation
     *
     * @return string[]
     */
    public function getStatisticTargetRelationPath(): array;
}
