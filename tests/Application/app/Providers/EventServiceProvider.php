<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\PlatformComparison;
use Polis\Observers\AggregatedModelObserver;
use Polis\Providers\BaseEventServiceProvider;

/**
 * Class EventServiceProvider
 */
class EventServiceProvider extends BaseEventServiceProvider
{
    public function getAppListenerMapping(): array
    {
        return [];
    }

    public function getAppUserMergeListeners(): array
    {
        return [];
    }

    public function registerObservers(): void
    {
        // Observe the self-cross comparison ledger so the incremental tuple-delta
        // maintenance path (multi-dimensional statistics) is exercised on writes.
        PlatformComparison::observe(AggregatedModelObserver::class);
    }
}
