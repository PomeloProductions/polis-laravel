<?php
declare(strict_types=1);

namespace Database\Factories\Messaging;

use App\Models\Messaging\Message;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Class ContactFactory
 * @package Database\Factories\User
 */
class MessageFactory extends Factory
{
    /**
     * @var string The related model
     */
    protected $model = Message::class;

    /**
     * @return array
     */
    public function definition()
    {
        return [
            'subject' => 'Test Subject',
            'email' => $this->faker->email,
            'template' => 'test_template',
            'data' => [],
            'via' => [],
            'to_id' => User::factory()->create()->id,
            'from_id' => User::factory()->create()->id,
        ];
    }
}
