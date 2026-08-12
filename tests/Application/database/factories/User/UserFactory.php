<?php
declare(strict_types=1);

namespace Database\Factories\User;

use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Class UserFactory
 * @package Database\Factories\User
 */
class UserFactory extends Factory
{
    /**
     * @var string The related model
     */
    protected $model = User::class;

    /**
     * @return array
     */
    public function definition()
    {
        return [
            'email' => $this->faker->unique()->safeEmail(),
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'password' => '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', // secret
        ];
    }
}
