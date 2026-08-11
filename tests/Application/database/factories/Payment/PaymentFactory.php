<?php
declare(strict_types=1);

namespace Database\Factories\Payment;

use App\Models\Payment\Payment;
use App\Models\Payment\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Class PaymentFactory
 * @package Database\Factories\Payment
 */
class PaymentFactory extends Factory
{
    /**
     * @var string The related model
     */
    protected $model = Payment::class;

    /**
     * @return array
     */
    public function definition()
    {
        return [
            'payment_method_id' => PaymentMethod::factory()->create()->id,
            'amount' => $this->faker->randomFloat(2, 0, 400),
        ];
    }
}
