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
 * Base route service provider for polis-laravel.
 *
 * Auto-bind behaviour
 * -------------------
 * Route::model() bindings resolve consumer overrides first and fall back to
 * the package concrete via {@see BaseServiceProvider::resolveConsumerOrPackage()}.
 * If a consumer hasn't supplied an `App\Models\...` subclass for any of the
 * package models, the package `Polis\Models\...` concrete is used instead.
 *
 * Still requires a consumer shim
 * ------------------------------
 * The route namespace `App\Http\<VERSION>\Controllers\...` is still
 * controller-driven, and every base controller in this package is abstract
 * (e.g. `Polis\Http\Core\Controllers\UserControllerAbstract`). Consumers
 * MUST therefore provide concrete subclasses at the resolved namespace
 * for each route registered in their routes/api-v1.php files. Bodies can
 * be empty.
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
     * Gets all model placeholders for the app.
     *
     * For each placeholder, the consumer-supplied `App\Models\...` class is
     * preferred; if absent, the package's `Polis\Models\...` concrete is used.
     */
    public function getModelPlaceholders(): array
    {
        return array_merge([
            'article' => BaseServiceProvider::resolveConsumerOrPackage(
                Article::class,
                \Polis\Models\Wiki\Article::class,
            ),
            'article_iteration' => BaseServiceProvider::resolveConsumerOrPackage(
                ArticleIteration::class,
                \Polis\Models\Wiki\ArticleIteration::class,
            ),
            'ballot' => BaseServiceProvider::resolveConsumerOrPackage(
                Ballot::class,
                \Polis\Models\Vote\Ballot::class,
            ),
            'ballot_completion' => BaseServiceProvider::resolveConsumerOrPackage(
                BallotCompletion::class,
                \Polis\Models\Vote\BallotCompletion::class,
            ),
            'category' => BaseServiceProvider::resolveConsumerOrPackage(
                Category::class,
                \Polis\Models\Category::class,
            ),
            'collection' => BaseServiceProvider::resolveConsumerOrPackage(
                Collection::class,
                \Polis\Models\Collection\Collection::class,
            ),
            'collection_item' => BaseServiceProvider::resolveConsumerOrPackage(
                CollectionItem::class,
                \Polis\Models\Collection\CollectionItem::class,
            ),
            'feature' => BaseServiceProvider::resolveConsumerOrPackage(
                Feature::class,
                \Polis\Models\Feature::class,
            ),
            'membership_plan' => BaseServiceProvider::resolveConsumerOrPackage(
                MembershipPlan::class,
                \Polis\Models\Subscription\MembershipPlan::class,
            ),
            'organization' => BaseServiceProvider::resolveConsumerOrPackage(
                Organization::class,
                \Polis\Models\Organization\Organization::class,
            ),
            'organization_manager' => BaseServiceProvider::resolveConsumerOrPackage(
                OrganizationManager::class,
                \Polis\Models\Organization\OrganizationManager::class,
            ),
            'payment_method' => BaseServiceProvider::resolveConsumerOrPackage(
                PaymentMethod::class,
                \Polis\Models\Payment\PaymentMethod::class,
            ),
            'role' => BaseServiceProvider::resolveConsumerOrPackage(
                Role::class,
                \Polis\Models\Role::class,
            ),
            'statistic' => BaseServiceProvider::resolveConsumerOrPackage(
                Statistic::class,
                \Polis\Models\Statistic\Statistic::class,
            ),
            'subscription' => BaseServiceProvider::resolveConsumerOrPackage(
                Subscription::class,
                \Polis\Models\Subscription\Subscription::class,
            ),
            'user' => BaseServiceProvider::resolveConsumerOrPackage(
                User::class,
                \Polis\Models\User\User::class,
            ),
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
