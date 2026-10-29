<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Release;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Class ReleaseFactory
 */
class ReleaseFactory extends Factory
{
    protected $model = Release::class;

    public function definition(): array
    {
        return [
            'game_id' => 1,
            'platform_id' => 1,
            'score' => null,
        ];
    }
}
