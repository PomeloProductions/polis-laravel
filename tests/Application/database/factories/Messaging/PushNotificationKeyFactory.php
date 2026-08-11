<?php
declare(strict_types=1);

namespace Database\Factories\Messaging;

use App\Models\Messaging\PushNotificationKey;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PushNotificationKeyFactory extends Factory {

    /**
     * @var string The related model
     */
    protected $model = PushNotificationKey::class;

    /**
     * @return array
     */
    public function definition()
    {
        return [
            'push_notification_key' => Str::random(40),
            'owner_id' => User::factory(),
            'owner_type' => 'user',
        ];
    }
}
