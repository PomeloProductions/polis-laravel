<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Wiki\AggregatableArticle;
use Polis\Providers\BaseRepositoryProvider;

class AppRepositoryProvider extends BaseRepositoryProvider
{
    public function appProviders(): array
    {
        return [];
    }

    public function appMorphMaps(): array
    {
        return [
            // Opt-in SQL push-down aggregation target used by the statistics
            // parity integration test (shares the articles table).
            'aggregatable_article' => AggregatableArticle::class,
        ];
    }

    public function registerApp(): void
    {
        // The dummy consumer app adds no app-specific repositories. The Todo
        // repositories are PolisOS-specific and live only in the PolisOS API.
    }
}
