<?php

declare(strict_types=1);

namespace Polis\Contracts\Models;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * Interface HasPaymentsContract
 */
interface HasPaymentsContract extends CanBeMorphedToContract
{
    /**
     * THe line items that are related to this model.
     * These act as the go between from this item and associated payments
     */
    public function lineItems(): MorphMany;

    /**
     * The payments related to this model.
     */
    public function payments(): MorphToMany;
}
