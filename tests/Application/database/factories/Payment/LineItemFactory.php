<?php
declare(strict_types=1);

namespace Database\Factories\Payment;

use App\Models\Payment\LineItem;
use App\Models\Payment\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Class PaymentFactory
 * @package Database\Factories\Payment
 */
class LineItemFactory extends Factory
{
    /**
     * @var string The related model
     */
    protected $model = LineItem::class;

    /**
     * @return array
     */
    public function definition()
    {
        return [
            'payment_id' => Payment::factory()->create()->id,
            'item_type' => 'donation',
            'amount' => (int) $this->faker->randomDigit(),
        ];
    }
}
