<?php

declare(strict_types=1);

namespace Polis\Tests\Fixtures\Models;

use Polis\Models\BaseModelAbstract;

/**
 * Fixture stub model used by the BaseModelCacheService / BaseModelCacheObserver
 * tests. Not aliased into the consumer App\Models\* namespace because no
 * consumer-side contract references it — it exists purely so the cache
 * tests have a concrete BaseModelAbstract subclass to manipulate.
 */
class CacheableWidget extends BaseModelAbstract
{
    protected $table = 'cacheable_widgets';
}
