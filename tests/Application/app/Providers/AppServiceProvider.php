<?php

declare(strict_types=1);

namespace App\Providers;

use Polis\Console\Commands\TodoApplyDailyIncrements;
use Polis\Console\Commands\TodoMigrateGroupsToNodes;
use Polis\Providers\BaseServiceProvider;

class AppServiceProvider extends BaseServiceProvider
{
    public function appProviders(): array
    {
        return [];
    }

    public function registerApp(): void
    {
        // The dummy consumer app registers no app-specific services. Register
        // the package's Todo console commands the way a consumer's console
        // kernel would (PolisOS loads them via Polis\Console\BaseKernel), so
        // the ported command tests can invoke them through $this->artisan().
        $this->commands([
            TodoApplyDailyIncrements::class,
            TodoMigrateGroupsToNodes::class,
        ]);
    }
}
