<?php

declare(strict_types=1);

namespace Polis\Providers;

use App\Models\Category;
use App\Models\Collection\Collection;
use App\Models\Collection\CollectionItem;
use App\Models\Feature;
use App\Models\Organization\Organization;
use App\Models\Organization\OrganizationManager;
use App\Models\Payment\PaymentMethod;
use App\Models\Role;
use App\Models\Statistic\Statistic;
use App\Models\Subscription\MembershipPlan;
use App\Models\Subscription\Subscription;
use App\Models\User\User;
use App\Models\Vote\Ballot;
use App\Models\Vote\BallotCompletion;
use App\Models\Wiki\Article;
use App\Models\Wiki\ArticleIteration;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

/**
 * Class RouteServiceProvider
 */
abstract class BaseRouteServiceProvider extends ServiceProvider
{
    /**
     * @var string[] All API versions that are currently available
     */
    protected $enabledAPIVersions = [
        'v1',
    ];

    /**
     * Gets all model placeholders for the app
     */
    public function getModelPlaceholders(): array
    {
        return array_merge([
            'article' => Article::class,
            'article_iteration' => ArticleIteration::class,
            'ballot' => Ballot::class,
            'ballot_completion' => BallotCompletion::class,
            'category' => Category::class,
            'collection' => Collection::class,
            'collection_item' => CollectionItem::class,
            'feature' => Feature::class,
            'membership_plan' => MembershipPlan::class,
            'organization' => Organization::class,
            'organization_manager' => OrganizationManager::class,
            'payment_method' => PaymentMethod::class,
            'role' => Role::class,
            'statistic' => Statistic::class,
            'subscription' => Subscription::class,
            'user' => User::class,
        ], $this->getAppModelPlaceholders());
    }

    /**
     * Gets all application specific model placeholders
     */
    abstract public function getAppModelPlaceholders(): array;

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        foreach ($this->getModelPlaceholders() as $placeHolder => $model) {
            Route::pattern($placeHolder, '^[0-9]+$');
            Route::model($placeHolder, $model);
        }

        parent::boot();
    }

    /**
     * Define the routes for the application.
     *
     * @return void
     */
    public function map()
    {
        foreach ($this->enabledAPIVersions as $version) {

            Route::middleware("api-{$version}")
                ->namespace('App\\Http\\'.strtoupper($version).'\\Controllers')
                ->group(base_path("routes/api-{$version}.php"));
        }
    }
}
