<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Validators;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Polis\Tests\TestCase;
use Polis\Validators\OwnedByValidator;

/**
 * Standalone-runnable version of OwnedByValidator coverage that does not
 * reference the consumer-app's Organization/PaymentMethod models. The
 * validator only requires the route object to expose dynamic properties
 * resolving to an Eloquent-like Collection, so we use stdClass-style
 * fixtures here.
 */
final class OwnedByValidatorStandaloneTest extends TestCase
{
    public function test_returns_true_when_id_is_contained_in_related_collection(): void
    {
        $member = (object) ['id' => 99];
        $parent = (object) ['paymentMethods' => new Collection([$member])];

        $request = mock(Request::class);
        $request->shouldReceive('route')->with('organization')->andReturn($parent);

        $validator = new OwnedByValidator($request);

        $this->assertTrue($validator->validate(
            'payment_method.1',
            99,
            ['organization', 'paymentMethods'],
        ));
    }

    public function test_returns_false_when_id_is_absent_from_related_collection(): void
    {
        $other = (object) ['id' => 1];
        $parent = (object) ['paymentMethods' => new Collection([$other])];

        $request = mock(Request::class);
        $request->shouldReceive('route')->with('organization')->andReturn($parent);

        $validator = new OwnedByValidator($request);

        $this->assertFalse($validator->validate(
            'payment_method.1',
            42,
            ['organization', 'paymentMethods'],
        ));
    }

    public function test_traverses_nested_relations(): void
    {
        $target = (object) ['id' => 7];
        $level2 = (object) ['items' => new Collection([$target])];
        $parent = (object) ['child' => $level2];

        $request = mock(Request::class);
        $request->shouldReceive('route')->with('organization')->andReturn($parent);

        $validator = new OwnedByValidator($request);

        $this->assertTrue($validator->validate(
            'item.1',
            7,
            ['organization', 'child', 'items'],
        ));
    }
}
