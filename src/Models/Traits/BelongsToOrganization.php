<?php

declare(strict_types=1);

namespace Polis\Models\Traits;

use App\Models\Organization\Organization;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Trait BelongsToOrganization
 */
trait BelongsToOrganization
{
    /**
     * The organization this device belongs to
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
