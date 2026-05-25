<?php

declare(strict_types=1);

namespace Polis\Tests\Integration\Repositories\Payment;

use App\Models\Payment\PaymentMethod;
use App\Models\User\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Polis\Repositories\Payment\PaymentMethodRepository;
use Polis\Tests\DatabaseSetupTrait;
use Polis\Tests\TestCase;
use Polis\Tests\Traits\MocksApplicationLog;

/**
 * Class PaymentMethodRepositoryTest
 */
final class PaymentMethodRepositoryTest extends TestCase
{
    use DatabaseSetupTrait, MocksApplicationLog;

    /**
     * @var PaymentMethodRepository
     */
    protected $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();

        $this->repository = new PaymentMethodRepository(
            new PaymentMethod,
            $this->getGenericLogMock(),
        );
    }

    public function test_find_all_success(): void
    {
        PaymentMethod::factory()->count(5)->create();
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
        $model = PaymentMethod::factory()->create();

        $foundModel = $this->repository->findOrFail($model->id);
        $this->assertEquals($model->id, $foundModel->id);
    }

    public function test_find_or_fail_fails(): void
    {
        PaymentMethod::factory()->create(['id' => 2]);

        $this->expectException(ModelNotFoundException::class);
        $this->repository->findOrFail(1);
    }

    public function test_create_success(): void
    {
        $user = User::factory()->create();

        /** @var PaymentMethod $paymentMethod */
        $paymentMethod = $this->repository->create([
            'payment_method_type' => 'cash',
            'owner_id' => $user->id,
            'owner_type' => 'user',
        ]);

        $this->assertEquals('cash', $paymentMethod->payment_method_type);
        $this->assertEquals($user->id, $paymentMethod->owner_id);
    }

    public function test_update_success(): void
    {
        $model = PaymentMethod::factory()->create([
            'payment_method_key' => 'test_key',
        ]);
        $this->repository->update($model, [
            'payment_method_key' => 'new_key',
        ]);

        /** @var PaymentMethod $updated */
        $updated = PaymentMethod::find($model->id);
        $this->assertEquals('new_key', $updated->payment_method_key);
    }

    public function test_delete_success(): void
    {
        $model = PaymentMethod::factory()->create();

        $this->repository->delete($model);

        $this->assertNull(PaymentMethod::find($model->id));
    }
}
