<?php
declare(strict_types=1);

namespace App\Providers;

use Polis\Providers\BaseRouteServiceProvider;

/**
 * Class RouteServiceProvider
 * @package App\Providers
 */
class RouteServiceProvider extends BaseRouteServiceProvider
{
    /**
     * Gets all application specific model placeholders
     *
     * @return array
     */
    public function getAppModelPlaceholders(): array
    {
        return [
        ];
    }
}
