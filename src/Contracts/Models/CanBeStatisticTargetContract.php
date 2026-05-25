<?php

declare(strict_types=1);

namespace Polis\Contracts\Models;

use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Interface CanBeStatisticTargetContract
 */
interface CanBeStatisticTargetContract extends CanBeMorphedToContract
{
    /**
     * Gets all statistics that belong to this model through a morph many relationship
     */
    public function targetStatistics(): MorphMany;
}
