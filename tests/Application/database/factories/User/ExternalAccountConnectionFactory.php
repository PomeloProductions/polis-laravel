<?php

declare(strict_types=1);

namespace Database\Factories\User;

use App\Models\User\ExternalAccountConnection;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Polis\Models\User\ExternalAccountConnection as AtheniaExternalAccountConnection;

/**
 * Class ExternalAccountConnectionFactory
 */
class ExternalAccountConnectionFactory extends Factory
{
    /**
     * @var string The related model
     */
    protected $model = ExternalAccountConnection::class;

    /**
     * @return array
     */
    public function definition()
    {
        return [
            'user_id' => User::factory()->create()->id,
            'provider' => 'github',
            'external_user_id' => (string) $this->faker->unique()->numberBetween(1, 1000000),
            'status' => AtheniaExternalAccountConnection::STATUS_CONNECTED,
            'token_expires_at' => null,
        ];
    }
}
