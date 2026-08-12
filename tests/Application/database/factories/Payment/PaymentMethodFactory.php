<?php
declare(strict_types=1);

namespace Database\Factories\Payment;

use App\Models\Payment\PaymentMethod;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Class PaymentFactory
 * @package Database\Factories\Payment
 */
class PaymentMethodFactory extends Factory
{
    /**
     * @var string The related model
     */
    protected $model = PaymentMethod::class;

    /**
     * @return array
     */
    public function definition()
    {
        return [
            'owner_id' => User::factory()->create()->id,
            'owner_type' => 'user',
            'payment_method_key' => $this->faker->text(20),
            'payment_method_type' => $this->faker->text(20),
        ];
    }
}
