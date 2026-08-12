<?php
declare(strict_types=1);

namespace Database\Factories\Statistic;

use App\Models\Statistic\Statistic;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Class StatisticFactory
 * @package Database\Factories\Statistics
 */
class StatisticFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Statistic::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->word,
            'model' => $this->faker->word,
            'relation' => $this->faker->word,
            'public' => $this->faker->boolean,
        ];
    }
} 