<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Services;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Database\Eloquent\Collection;
use Polis\Contracts\Repositories\BaseRepositoryContract;
use Polis\Services\BaseModelCacheService;
use Polis\Tests\CustomMockInterface;
use Polis\Tests\Fixtures\Models\CacheableWidget;
use Polis\Tests\TestCase;
use Psr\SimpleCache\InvalidArgumentException;

/**
 * Class BaseModelCacheServiceTest
 *
 * Mirrors the original Lingwave WordCacheServiceTest one-to-one for the
 * code paths that survived the port: hit, miss, find-by-id, model insert,
 * model replace, model removal, and explicit `forget()` invalidation. The
 * cache repository is mocked at the contract level (no Redis required).
 */
final class BaseModelCacheServiceTest extends TestCase
{
    /** @var Repository|CustomMockInterface */
    private $cache;

    /** @var BaseRepositoryContract|CustomMockInterface */
    private $repository;

    private BaseModelCacheService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cache = mock(Repository::class);
        $this->repository = mock(BaseRepositoryContract::class);

        $this->service = new BaseModelCacheService(
            'widgets',
            $this->cache,
            $this->repository,
        );
    }

    public function test_get_all_models_returns_repository_result_on_miss(): void
    {
        $widget = new CacheableWidget;
        $widget->id = 1;
        $widgets = new Collection([$widget]);

        $this->cache->shouldReceive('has')->with('widgets')->andReturnFalse();
        $this->repository->shouldReceive('findAll')->andReturn($widgets);
        $this->cache->shouldReceive('put')->with('widgets', $widgets)->once();

        $this->assertSame($widgets, $this->service->getAllModels());
    }

    public function test_get_all_models_returns_cached_collection_on_hit(): void
    {
        $widget = new CacheableWidget;
        $widget->id = 1;
        $widgets = new Collection([$widget]);

        $this->cache->shouldReceive('has')->with('widgets')->andReturnTrue();
        $this->cache->shouldReceive('get')->with('widgets')->andReturn($widgets);
        $this->cache->shouldNotReceive('put');
        $this->repository->shouldNotReceive('findAll');

        $this->assertSame($widgets, $this->service->getAllModels());
    }

    public function test_remember_propagates_loader_result_through_to_cache_on_miss(): void
    {
        $widget = new CacheableWidget;
        $widget->id = 99;
        $widgets = new Collection([$widget]);

        $this->cache->shouldReceive('has')->with('widgets')->andReturnFalse();
        $this->cache->shouldReceive('put')->with('widgets', $widgets)->once();

        $invoked = 0;
        $result = $this->service->remember(function () use ($widgets, &$invoked) {
            $invoked++;

            return $widgets;
        });

        $this->assertSame($widgets, $result);
        $this->assertSame(1, $invoked, 'loader must be invoked exactly once on a miss');
    }

    public function test_remember_skips_loader_on_hit(): void
    {
        $widgets = new Collection([new CacheableWidget]);

        $this->cache->shouldReceive('has')->with('widgets')->andReturnTrue();
        $this->cache->shouldReceive('get')->with('widgets')->andReturn($widgets);
        $this->cache->shouldNotReceive('put');

        $result = $this->service->remember(function () {
            $this->fail('loader must not be invoked when the key is in cache');
        });

        $this->assertSame($widgets, $result);
    }

    public function test_remember_recovers_from_invalid_argument_exception_and_loads(): void
    {
        $widget = new CacheableWidget;
        $widget->id = 7;
        $widgets = new Collection([$widget]);

        $this->cache->shouldReceive('has')
            ->with('widgets')
            ->andThrow(new class extends \Exception implements InvalidArgumentException {});
        $this->cache->shouldReceive('put')->with('widgets', $widgets)->once();

        $result = $this->service->remember(fn () => $widgets);

        $this->assertSame($widgets, $result);
    }

    public function test_forget_invalidates_the_key(): void
    {
        $this->cache->shouldReceive('forget')->with('widgets')->once();

        $this->service->forget();
    }

    public function test_find_by_id_returns_matching_model(): void
    {
        $a = new CacheableWidget;
        $a->id = 1;
        $b = new CacheableWidget;
        $b->id = 2;
        $widgets = new Collection([$a, $b]);

        $this->cache->shouldReceive('has')->with('widgets')->andReturnTrue();
        $this->cache->shouldReceive('get')->with('widgets')->andReturn($widgets);

        $this->assertSame($b, $this->service->findById(2));
    }

    public function test_find_by_id_returns_null_when_missing(): void
    {
        $a = new CacheableWidget;
        $a->id = 1;
        $widgets = new Collection([$a]);

        $this->cache->shouldReceive('has')->with('widgets')->andReturnTrue();
        $this->cache->shouldReceive('get')->with('widgets')->andReturn($widgets);

        $this->assertNull($this->service->findById(999));
    }

    public function test_cache_model_appends_new_model_to_collection(): void
    {
        $existing = new CacheableWidget;
        $existing->id = 1;
        $widgets = new Collection([$existing]);

        $this->cache->shouldReceive('has')->with('widgets')->andReturnTrue();
        $this->cache->shouldReceive('get')->with('widgets')->andReturn($widgets);

        $new = new CacheableWidget;
        $new->id = 2;

        $this->cache->shouldReceive('put')
            ->with('widgets', \Mockery::on(function (Collection $written) {
                $this->assertCount(2, $written);
                $this->assertEqualsCanonicalizing([1, 2], $written->pluck('id')->all());

                return true;
            }))
            ->once();

        $this->service->cacheModel($new);
    }

    public function test_cache_model_replaces_existing_model_with_same_id(): void
    {
        $original = new CacheableWidget(['name' => 'old']);
        $original->id = 5;
        $widgets = new Collection([$original]);

        $this->cache->shouldReceive('has')->with('widgets')->andReturnTrue();
        $this->cache->shouldReceive('get')->with('widgets')->andReturn($widgets);

        $replacement = new CacheableWidget(['name' => 'new']);
        $replacement->id = 5;

        $this->cache->shouldReceive('put')
            ->with('widgets', \Mockery::on(function (Collection $written) {
                $this->assertCount(1, $written, 'replacement must not duplicate the row');
                $this->assertSame('new', $written->first()->name);

                return true;
            }))
            ->once();

        $this->service->cacheModel($replacement);
    }

    public function test_remove_model_drops_the_matching_row(): void
    {
        $a = new CacheableWidget;
        $a->id = 1;
        $b = new CacheableWidget;
        $b->id = 2;
        $widgets = new Collection([$a, $b]);

        $this->cache->shouldReceive('has')->with('widgets')->andReturnTrue();
        $this->cache->shouldReceive('get')->with('widgets')->andReturn($widgets);

        $this->cache->shouldReceive('put')
            ->with('widgets', \Mockery::on(function (Collection $written) {
                $this->assertCount(1, $written);
                $this->assertSame(1, $written->first()->id);

                return true;
            }))
            ->once();

        $this->service->removeModel($b);
    }

    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }
}
