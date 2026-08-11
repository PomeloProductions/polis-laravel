<?php

declare(strict_types=1);

namespace App\Providers;

use Polis\Providers\BaseRouteServiceProvider;

/**
 * Class RouteServiceProvider
 */
class RouteServiceProvider extends BaseRouteServiceProvider
{
    /**
     * Gets all application specific model placeholders
     */
    public function getAppModelPlaceholders(): array
    {
        return [
        ];
    }
}
