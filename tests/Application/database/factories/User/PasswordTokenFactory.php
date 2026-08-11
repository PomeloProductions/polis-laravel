<?php
declare(strict_types=1);

namespace Database\Factories\User;

use App\Models\User\PasswordToken;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Class ContactFactory
 * @package Database\Factories\User
 */
class PasswordTokenFactory extends Factory
{
    /**
     * @var string The related model
     */
    protected $model = PasswordToken::class;

    /**
     * @return array
     */
    public function definition()
    {
        return [
            'token' => Str::random(40),
            'user_id' => User::factory()->create()->id,
        ];
    }
}
