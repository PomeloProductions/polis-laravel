<?php

declare(strict_types=1);

namespace Polis\Tests\Integration\Repositories\Payment;

use App\Models\Payment\LineItem;
use App\Models\Payment\Payment;
use App\Models\Payment\PaymentMethod;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Polis\Repositories\Payment\LineItemRepository;
use Polis\Repositories\Payment\PaymentRepository;
use Polis\Tests\Application\ApplicationTestCase;
use Polis\Tests\Traits\MocksApplicationLog;

/**
 * Class PaymentRepositoryTest
 */
final class PaymentRepositoryTest extends ApplicationTestCase
{
    use MocksApplicationLog;

    /**
     * @var PaymentRepository
     */
    protected $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();

        $this->repository = new PaymentRepository(
            new Payment,
            $this->getGenericLogMock(),
            new LineItemRepository(
                new LineItem,
                $this->getGenericLogMock()
            ),
        );
    }

    public function test_find_all_success(): void
    {
        Payment::factory()->count(5)->create();
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
        $model = Payment::factory()->create();

        $foundModel = $this->repository->findOrFail($model->id);
        $this->assertEquals($model->id, $foundModel->id);
    }

    public function test_find_or_fail_fails(): void
    {
        Payment::factory()->create(['id' => 2]);

        $this->expectException(ModelNotFoundException::class);
        $this->repository->findOrFail(1);
    }

    public function test_create_success(): void
    {
        $paymentMethod = PaymentMethod::factory()->create();

        /** @var Payment $payment */
        $payment = $this->repository->create([
            'amount' => 11.32,
        ], $paymentMethod);

        $this->assertEquals(11.32, $payment->amount);
        $this->assertEquals($paymentMethod->id, $payment->payment_method_id);
    }

    public function test_create_success_with_line_items(): void
    {
        $paymentMethod = PaymentMethod::factory()->create();

        /** @var Payment $payment */
        $payment = $this->repository->create([
            'amount' => 11.32,
            'line_items' => [
                [
                    'item_type' => 'donation',
                    'amount' => 11.32,
                ],
            ],
        ], $paymentMethod);

        $this->assertEquals(11.32, $payment->amount);
        $this->assertEquals($paymentMethod->id, $payment->payment_method_id);
        $this->assertCount(1, $payment->lineItems);
    }

    public function test_update_success(): void
    {
        $model = Payment::factory()->create();
        LineItem::factory()->create([
            'payment_id' => $model->id,
        ]);
        $this->repository->update($model, [
            'refunded_at' => Carbon::now(),
        ]);

        /** @var Payment $updated */
        $updated = Payment::find($model->id);
        $this->assertNotNull($updated->refunded_at);
        $this->assertCount(1, $updated->lineItems);
    }

    public function test_update_success_with_line_items(): void
    {
        $model = Payment::factory()->create();
        $keep = LineItem::factory()->create([
            'payment_id' => $model->id,
        ]);
        LineItem::factory()->create([
            'payment_id' => $model->id,
        ]);
        $this->repository->update($model, [
            'refunded_at' => Carbon::now(),
            'line_items' => [
                [
                    'id' => $keep->id,
                    'item_type' => 'donation',
                    'amount' => 2.11,
                ],
                [
                    'item_type' => 'donation',
                    'amount' => 12.11,
                ],
                [
                    'item_type' => 'donation',
                    'amount' => 1.11,
                ],
            ],
        ]);

        /** @var Payment $updated */
        $updated = Payment::find($model->id);
        $this->assertNotNull($updated->refunded_at);
        $this->assertCount(3, $updated->lineItems);
    }

    public function test_delete_success(): void
    {
        $model = Payment::factory()->create();

        $this->repository->delete($model);

        $this->assertNull(Payment::find($model->id));
    }
}
