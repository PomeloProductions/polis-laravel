<?php

declare(strict_types=1);

namespace Polis\Models\Traits;

use App\Models\Payment\LineItem;
use App\Models\Payment\Payment;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * Trait HasPayments
 */
trait HasPayments
{
    /**
     * The purchased item instances for this subscription
     */
    public function lineItems(): MorphMany
    {
        return $this->morphMany(LineItem::class, 'item');
    }

    /**
     * The payments that have been made for this subscription
     */
    public function payments(): MorphToMany
    {
        return $this->morphToMany(Payment::class, 'item', 'line_items');
    }
}
