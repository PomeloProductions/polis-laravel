<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Observers;

use Polis\Contracts\Services\ModelCacheServiceContract;
use Polis\Observers\BaseModelCacheObserver;
use Polis\Tests\CustomMockInterface;
use Polis\Tests\Fixtures\Models\CacheableWidget;
use Polis\Tests\TestCase;

/**
 * Class BaseModelCacheObserverTest
 *
 * Mirrors Lingwave's WordCacheObserverTest: each of created/updated/deleting
 * forwards to the matching cache-service method exactly once. The cache
 * service is mocked at the contract level; the observer never touches the
 * underlying cache repository directly.
 */
final class BaseModelCacheObserverTest extends TestCase
{
    /** @var ModelCacheServiceContract|CustomMockInterface */
    private $cacheService;

    private BaseModelCacheObserver $observer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cacheService = mock(ModelCacheServiceContract::class);
        $this->observer = new BaseModelCacheObserver($this->cacheService);
    }

    public function test_created_forwards_to_cache_model(): void
    {
        $widget = new CacheableWidget;
        $widget->id = 1;

        $this->cacheService->shouldReceive('cacheModel')->once()->with($widget);

        $this->observer->created($widget);
    }

    public function test_updated_forwards_to_cache_model(): void
    {
        $widget = new CacheableWidget;
        $widget->id = 1;

        $this->cacheService->shouldReceive('cacheModel')->once()->with($widget);

        $this->observer->updated($widget);
    }

    public function test_deleting_forwards_to_remove_model(): void
    {
        $widget = new CacheableWidget;
        $widget->id = 1;

        $this->cacheService->shouldReceive('removeModel')->once()->with($widget);

        $this->observer->deleting($widget);
    }

    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }
}
