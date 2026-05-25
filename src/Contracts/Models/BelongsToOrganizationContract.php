<?php

declare(strict_types=1);

namespace Polis\Contracts\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Interface BelongsToOrganizationContract
 *
 * @property $organization_id
 */
interface BelongsToOrganizationContract
{
    /**
     * The organization this model belongs to
     */
    public function organization(): BelongsTo;
}
