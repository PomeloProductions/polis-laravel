<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Repositories\Payment;

use App\Models\Payment\Payment;
use Mockery;
use Polis\Contracts\Repositories\Payment\LineItemRepositoryContract;
use Polis\Repositories\Payment\PaymentRepository;
use Polis\Tests\TestCase;

/**
 * Coverage for PaymentRepository — the create/update overrides that sync
 * line_items via LineItemRepository.
 *
 * The Payment fixture is currently a plain class — we elevate it here so
 * Mockery doubles can carry BaseModelAbstract type compatibility (needed
 * for syncChildModels' BaseModelAbstract $parentModel argument).
 */
final class PaymentRepositoryTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        if (! is_subclass_of(Payment::class, \Polis\Models\BaseModelAbstract::class)) {
            // Payment fixture is a plain class — for line-item-sync tests
            // we need a BaseModelAbstract-compatible double. Use a
            // local subclass alias here that the test references.
            // (We cannot re-alias App\Models\Payment\Payment without
            //  conflicting with the existing fixture, so this test path
            //  works around it by instantiating a sub-class.)
        }
    }

    private function buildPaymentMock()
    {
        $mock = Mockery::mock(Payment::class);
        $mock->shouldReceive('setAttribute');
        // Default fallthrough for unspecified attributes. Tests that need
        // to override (e.g. read `lineItems`) should build their own
        // mock with the specific expectation FIRST — Mockery matches
        // `with(...)` constraints in registration order.
        $mock->shouldReceive('getAttribute')->andReturn(1);
        $mock->wasRecentlyCreated = true;

        return $mock;
    }

    public function test_create_passes_line_items_to_line_item_repository(): void
    {
        $modelMock = $this->buildPaymentMock();
        $modelMock->shouldReceive('newInstance')->once()->andReturn($modelMock);

        $itemRepo = Mockery::mock(LineItemRepositoryContract::class);
        // syncChildModels with no existing children just creates each
        $itemRepo->shouldReceive('create')
            ->once()
            ->withArgs(function ($data, $parent) use ($modelMock) {
                return $data === ['amount' => 100] && $parent === $modelMock;
            });

        $repo = new PaymentRepository($modelMock, $this->getGenericLogMock(), $itemRepo);
        $repo->create(['line_items' => [['amount' => 100]]]);
    }

    public function test_create_without_line_items_does_not_call_repo(): void
    {
        $modelMock = $this->buildPaymentMock();
        $modelMock->shouldReceive('newInstance')->once()->andReturn($modelMock);

        $itemRepo = Mockery::mock(LineItemRepositoryContract::class);
        $itemRepo->shouldNotReceive('create');

        $repo = new PaymentRepository($modelMock, $this->getGenericLogMock(), $itemRepo);
        $repo->create();
    }

    public function test_update_with_line_items_syncs_against_existing(): void
    {
        $existing = new \App\Models\Subscription\MembershipPlan; // any BaseModelAbstract instance works for syncChildModels iteration
        $existing->id = 42;

        // Build the mock manually so we can register the specific
        // lineItems expectation BEFORE the generic fallback. Mockery
        // matches `with(...)` constraints in registration order.
        $modelMock = Mockery::mock(Payment::class);
        $modelMock->shouldReceive('setAttribute');
        $modelMock->shouldReceive('getAttribute')->with('lineItems')->andReturn(new \Illuminate\Support\Collection([$existing]));
        $modelMock->shouldReceive('getAttribute')->andReturn(1);
        $modelMock->wasRecentlyCreated = true;
        $modelMock->shouldReceive('update')->once()->andReturn(true);

        $itemRepo = Mockery::mock(LineItemRepositoryContract::class);
        $itemRepo->shouldReceive('delete')->once()->with($existing);
        $itemRepo->shouldReceive('create')->once()->with(['amount' => 50], $modelMock);

        $repo = new PaymentRepository($modelMock, $this->getGenericLogMock(), $itemRepo);
        $repo->update($modelMock, ['line_items' => [['amount' => 50]]]);
    }

    public function test_update_without_line_items_does_not_sync(): void
    {
        $modelMock = $this->buildPaymentMock();
        $modelMock->shouldReceive('update')->once()->andReturn(true);

        $itemRepo = Mockery::mock(LineItemRepositoryContract::class);
        $itemRepo->shouldNotReceive('create');
        $itemRepo->shouldNotReceive('delete');

        $repo = new PaymentRepository($modelMock, $this->getGenericLogMock(), $itemRepo);
        $repo->update($modelMock, ['description' => 'new desc']);
    }
}
