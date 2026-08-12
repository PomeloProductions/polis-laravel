<?php
namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Class AssetFactory
 * @package Database\Factories
 */
class CategoryFactory extends Factory
{
    /**
     * @var string The related model
     */
    protected $model = Category::class;

    /**
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->name,
            'description' => null,
        ];
    }
}
