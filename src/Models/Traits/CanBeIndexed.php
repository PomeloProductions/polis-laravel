<?php

declare(strict_types=1);

namespace Polis\Models\Traits;

use App\Models\Resource;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * Trait CanBeIndexed
 */
trait CanBeIndexed
{
    /**
     * The resource object for this indexable model
     */
    public function resource(): MorphOne
    {
        return $this->morphOne(Resource::class, 'resource');
    }
}
