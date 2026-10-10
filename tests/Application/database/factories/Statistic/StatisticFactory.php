<?php
declare(strict_types=1);

namespace Database\Factories\Statistic;

use App\Models\Statistic\Statistic;
use App\Models\User\User;
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
     * The default statistic is GLOBAL (NULL owner) — the pre-owner behaviour —
     * so existing tests keep producing shared/public statistics unchanged.
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
            'owner_type' => null,
            'owner_id' => null,
        ];
    }

    /**
     * A statistic owned by a specific user (per-user statistic). When no user
     * is given, one is created.
     */
    public function ownedByUser(?User $user = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'owner_type' => 'user',
            'owner_id' => ($user ?? User::factory()->create())->id,
        ]);
    }
}