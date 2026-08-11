<?php
namespace Database\Factories;

use App\Models\Resource;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Class UserFactory
 * @package Database\Factories
 */
class ResourceFactory extends Factory
{
    /**
     * @var string The related model
     */
    protected $model = Resource::class;

    /**
     * @return array
     */
    public function definition()
    {
        return [
            'content' => $this->faker->text,
            'resource_type' => 'user',
            'resource_id' => User::factory()->create()->id,
        ];
    }
}
