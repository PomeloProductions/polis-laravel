<?php

declare(strict_types=1);

namespace App\Providers;

use Polis\Providers\BaseServiceProvider;

class AppServiceProvider extends BaseServiceProvider
{
    public function appProviders(): array
    {
        return [];
    }

    public function registerApp(): void
    {
        // The dummy consumer app registers no app-specific services. The Todo
        // subsystem is PolisOS-specific and intentionally lives only in the
        // PolisOS API app, not in the package's test harness.
    }
}
