<?php
namespace Database\Factories;

use App\Models\Asset;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Class AssetFactory
 * @package Database\Factories
 */
class AssetFactory extends Factory
{
    /**
     * @var string The related model
     */
    protected $model = Asset::class;

    /**
     * @return array
     */
    public function definition()
    {
        return [
            'url' => $this->faker->url,
        ];
    }
}
