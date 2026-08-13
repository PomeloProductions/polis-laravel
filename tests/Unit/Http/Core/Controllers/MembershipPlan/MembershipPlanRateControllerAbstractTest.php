<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Http\Core\Controllers\MembershipPlan;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Mockery;
use Polis\Contracts\Repositories\Subscription\MembershipPlanRateRepositoryContract;
use Polis\Tests\Fixtures\Controllers\MembershipPlan\MembershipPlanRateController;
use Polis\Tests\Fixtures\Models\MembershipPlan as MembershipPlanFixture;
use Polis\Tests\Unit\Http\Core\Controllers\ControllerTestCase;

/**
 * Unit coverage for MembershipPlan\MembershipPlanRateControllerAbstract.
 *
 * MembershipPlan-scoped read-only listing.
 */
final class MembershipPlanRateControllerAbstractTest extends ControllerTestCase
{
    public function test_index_scopes_find_all_to_parent_membership_plan(): void
    {
        $repo = Mockery::mock(MembershipPlanRateRepositoryContract::class);
        $paginator = Mockery::mock(LengthAwarePaginator::class);
        $request = $this->makeIndexRequest(
            'Polis\\Http\\Core\\Requests\\MembershipPlan\\MembershipPlanRate\\IndexRequest',
        );
        $plan = Mockery::mock(MembershipPlanFixture::class);

        $repo->shouldReceive('findAll')
            ->once()
            ->with([], [], [], [], 10, [$plan], 1)
            ->andReturn($paginator);

        $this->assertSame($paginator, (new MembershipPlanRateController($repo))->index($request, $plan));
    }
}
