<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Services\Statistic;

use Polis\Services\Statistic\MultiDimensionalAggregationService;
use Polis\Support\Statistic\DateBucketResolver;
use Polis\Tests\TestCase;
use ReflectionMethod;

/**
 * Class QualifyColumnReferenceTest
 *
 * Unit coverage for the engine's qualify() helper: a bare column is prefixed with
 * the reserved `base` alias; an already-qualified `alias.column` is returned
 * untouched (so axes/filters/measure/target can reference a joined table). This
 * is the mechanism that keeps a no-joins statistic byte-for-byte compatible.
 */
final class QualifyColumnReferenceTest extends TestCase
{
    private function qualify(string $reference): string
    {
        $service = new MultiDimensionalAggregationService(new DateBucketResolver);
        $method = new ReflectionMethod($service, 'qualify');
        $method->setAccessible(true);

        return $method->invoke($service, $reference);
    }

    public function test_bare_column_is_prefixed_with_base_alias(): void
    {
        $this->assertSame('base.owner_id', $this->qualify('owner_id'));
        $this->assertSame('base.id', $this->qualify('id'));
    }

    public function test_already_qualified_reference_is_untouched(): void
    {
        $this->assertSame('rd.release_date', $this->qualify('rd.release_date'));
        $this->assertSame('c.owner_id', $this->qualify('c.owner_id'));
        $this->assertSame('cic.category_id', $this->qualify('cic.category_id'));
    }

    public function test_base_qualified_reference_is_left_as_is(): void
    {
        // An explicit base.<col> already contains a dot and is returned verbatim.
        $this->assertSame('base.id', $this->qualify('base.id'));
    }
}
