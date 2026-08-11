<?php

declare(strict_types=1);

namespace App\Providers;

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
        // No app-specific observers in the dummy consumer app. The Todo
        // balance-log / page-component observers are PolisOS-specific.
    }
}
