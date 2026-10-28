<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\PlatformComparison;
use App\Models\Release;
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
            // Base-table morph for the multi-dimensional intersection-statistics
            // tests (self-cross platform comparison ledger).
            'platform_comparison' => PlatformComparison::class,
            // Base-table morph for the declarative-joins tests (HSC releases,
            // dates in a joined one-to-many release_dates table).
            'release' => Release::class,
        ];
    }

    public function registerApp(): void
    {
        // The dummy consumer app adds no app-specific repositories. The Todo
        // repositories are PolisOS-specific and live only in the PolisOS API.
    }
}
