<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Models\Traits;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Mockery;
use Polis\Contracts\Repositories\BaseRepositoryContract;
use Polis\Contracts\Services\ModelCacheServiceContract;
use Polis\Models\BaseModelAbstract;
use Polis\Models\Traits\HasModelCache;
use Polis\Observers\BaseModelCacheObserver;
use Polis\Services\BaseModelCacheService;
use Polis\Tests\TestCase;

/**
 * Class HasModelCacheTest
 *
 * Validates the trait opt-in path: when a model uses HasModelCache, native
 * Eloquent lifecycle events fire the observer, which in turn forwards to
 * the cache service. Tests use Eloquent's "eloquent.{event}: {class}"
 * dispatcher channel so we exercise the same wiring `observe()` installs
 * without needing a real database.
 */
final class HasModelCacheTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Eloquent caches the result of `boot` per-class. Clearing the cache
        // forces each test to re-run `bootHasModelCache` so the observer
        // re-registers against the current container bindings.
        Model::clearBootedModels();

        // Default the trait's guard on, individual tests flip it.
        config(['polis.model_cache.enabled' => true]);

        // Eloquent looks up its event dispatcher via the model's static
        // `$dispatcher` property; Orchestra Testbench binds one already, but
        // make the binding explicit so `Model::observe()` picks it up.
        Model::setEventDispatcher($this->app['events']);
    }

    public function test_created_event_triggers_cache_model_via_observer(): void
    {
        $cacheService = mock(ModelCacheServiceContract::class);
        $this->app->bind(ModelCacheServiceContract::class, fn () => $cacheService);

        // Touching the model class boots it, which calls `bootHasModelCache`
        // and registers the observer against the event dispatcher.
        $widget = new TraitWiringWidget;
        $widget->id = 42;

        $cacheService->shouldReceive('cacheModel')->once()->with(Mockery::on(
            fn ($passed) => $passed instanceof TraitWiringWidget && $passed->id === 42
        ));

        // "eloquent.{event}: {class}" is Eloquent's canonical event channel
        // — `observe()` listens on exactly this name internally.
        $this->app['events']->dispatch(
            'eloquent.created: '.TraitWiringWidget::class,
            [$widget],
        );
    }

    public function test_deleting_event_triggers_remove_model_via_observer(): void
    {
        $cacheService = mock(ModelCacheServiceContract::class);
        $this->app->bind(ModelCacheServiceContract::class, fn () => $cacheService);

        $widget = new TraitWiringWidget;
        $widget->id = 42;

        $cacheService->shouldReceive('removeModel')->once()->with(Mockery::on(
            fn ($passed) => $passed instanceof TraitWiringWidget && $passed->id === 42
        ));

        $this->app['events']->dispatch(
            'eloquent.deleting: '.TraitWiringWidget::class,
            [$widget],
        );
    }

    public function test_observer_is_skipped_when_config_disables_model_cache(): void
    {
        config(['polis.model_cache.enabled' => false]);

        // If the trait registered the observer, an unbound contract resolve
        // would explode when the event fired (the default contract binding
        // throws a RuntimeException). Asserting no exception is the proof
        // that boot short-circuited.
        $widget = new TraitWiringDisabledWidget;
        $widget->id = 9;

        $this->app['events']->dispatch(
            'eloquent.created: '.TraitWiringDisabledWidget::class,
            [$widget],
        );

        $this->assertTrue(true, 'no observer wiring means no exception when the event fires');
    }

    public function test_observer_round_trips_through_base_model_cache_service(): void
    {
        // Integration-flavoured check: a real BaseModelCacheService with a
        // mocked cache repository confirms the observer + service contract
        // wire end-to-end without a database.
        $cache = mock(Repository::class);
        $repository = mock(BaseRepositoryContract::class);

        $cache->shouldReceive('has')->with('widgets')->andReturnTrue();
        $cache->shouldReceive('get')->with('widgets')->andReturn(new Collection);
        $cache->shouldReceive('put')->with('widgets', Mockery::type(Collection::class))->once();

        $service = new BaseModelCacheService('widgets', $cache, $repository);
        $observer = new BaseModelCacheObserver($service);

        $widget = new TraitWiringWidget;
        $widget->id = 1;

        $observer->created($widget);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        Model::clearBootedModels();
        parent::tearDown();
    }
}

/**
 * Local fixture: a BaseModelAbstract subclass that opts into the trait.
 * Declared in the same file so the tests own its boot lifecycle.
 */
class TraitWiringWidget extends BaseModelAbstract
{
    use HasModelCache;

    protected $table = 'trait_wiring_widgets';
}

class TraitWiringDisabledWidget extends BaseModelAbstract
{
    use HasModelCache;

    protected $table = 'trait_wiring_disabled_widgets';
}
