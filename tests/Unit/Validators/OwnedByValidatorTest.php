<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Validators;

use App\Models\Organization\Organization;
use App\Models\Payment\PaymentMethod;
use Illuminate\Http\Request;
use Polis\Tests\TestCase;
use Polis\Validators\OwnedByValidator;

/**
 * Class OwnedByTest
 */
final class OwnedByValidatorTest extends TestCase
{
    public function test_owned_by_validator_false(): void
    {
        $paymentMethod = new PaymentMethod;
        $paymentMethod->id = 2345;
        $organization = new Organization([
            'paymentMethods' => collect(),
        ]);
        $organization->id = 234;

        $request = mock(Request::class);
        $request->shouldReceive('route')->with('organization')->andReturn($organization);

        $ownedBy = new OwnedByValidator($request);

        $params = ['organization', 'paymentMethods'];

        $this->assertFalse($ownedBy->validate('payment_method.1', 2345, $params));
    }

    public function test_owned_by_validator_true(): void
    {
        $paymentMethod = new PaymentMethod;
        $paymentMethod->id = 2345;
        $organization = new Organization([
            'paymentMethods' => collect([
                $paymentMethod,
            ]),
        ]);
        $organization->id = 234;

        $request = mock(Request::class);
        $request->shouldReceive('route')->with('organization')->andReturn($organization);

        $ownedBy = new OwnedByValidator($request);

        $params = ['organization', 'paymentMethods'];

        $this->assertTrue($ownedBy->validate('payment_method.1', 2345, $params));
    }
}
