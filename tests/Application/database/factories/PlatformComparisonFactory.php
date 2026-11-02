<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\PlatformComparison;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Class PlatformComparisonFactory
 */
class PlatformComparisonFactory extends Factory
{
    protected $model = PlatformComparison::class;

    public function definition(): array
    {
        return [
            'owner_id' => 1,
            'voting_type' => 'best',
            'platform_a_id' => 1,
            'platform_b_id' => 2,
            'comparison_group_id' => null,
            'compared_at' => now(),
        ];
    }
}
