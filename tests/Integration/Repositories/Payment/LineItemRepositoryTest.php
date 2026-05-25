<?php

declare(strict_types=1);

namespace Polis\Tests\Integration\Repositories\Payment;

use App\Models\Payment\LineItem;
use App\Models\Payment\Payment;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Polis\Repositories\Payment\LineItemRepository;
use Polis\Tests\DatabaseSetupTrait;
use Polis\Tests\TestCase;
use Polis\Tests\Traits\MocksApplicationLog;

/**
 * Class LineItemRepositoryTest
 */
final class LineItemRepositoryTest extends TestCase
{
    use DatabaseSetupTrait, MocksApplicationLog;

    /**
     * @var LineItemRepository
     */
    protected $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();

        $this->repository = new LineItemRepository(
            new LineItem,
            $this->getGenericLogMock(),
        );
    }

    public function test_find_all_success(): void
    {
        LineItem::factory()->count(5)->create();
        $items = $this->repository->findAll();
        $this->assertCount(5, $items);
    }

    public function test_find_all_empty(): void
    {
        $items = $this->repository->findAll();
        $this->assertEmpty($items);
    }

    public function test_find_or_fail_success(): void
    {
        $model = LineItem::factory()->create();

        $foundModel = $this->repository->findOrFail($model->id);
        $this->assertEquals($model->id, $foundModel->id);
    }

    public function test_find_or_fail_fails(): void
    {
        LineItem::factory()->create(['id' => 2]);

        $this->expectException(ModelNotFoundException::class);
        $this->repository->findOrFail(1);
    }

    public function test_create_success(): void
    {
        $payment = Payment::factory()->create();

        /** @var LineItem $lineItem */
        $lineItem = $this->repository->create([
            'amount' => 11.32,
            'item_type' => 'donation',
        ], $payment);

        $this->assertEquals(11.32, $lineItem->amount);
        $this->assertEquals($payment->id, $lineItem->payment_id);
    }

    public function test_update_success(): void
    {
        $model = LineItem::factory()->create([
            'amount' => 11.32,
        ]);
        $this->repository->update($model, [
            'amount' => 124.32,
        ]);

        /** @var LineItem $updated */
        $updated = LineItem::find($model->id);
        $this->assertEquals(124.32, $updated->amount);
    }

    public function test_delete_success(): void
    {
        $model = LineItem::factory()->create();

        $this->repository->delete($model);

        $this->assertNull(LineItem::find($model->id));
    }
}
