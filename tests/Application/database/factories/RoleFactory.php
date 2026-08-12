<?php
namespace Database\Factories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Class RoleFactory
 * @package Database\Factories
 */
class RoleFactory extends Factory
{
    /**
     * @var string The related model
     */
    protected $model = Role::class;

    /**
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->unique()->word,
        ];
    }
}
