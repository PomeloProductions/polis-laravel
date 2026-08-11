<?php
declare(strict_types=1);

namespace Database\Factories\User;

use App\Models\User\InvitationToken;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Class InvitationTokenFactory
 * @package Database\Factories\User
 */
class InvitationTokenFactory extends Factory
{
    /**
     * @var string The related model
     */
    protected $model = InvitationToken::class;

    /**
     * @return array
     */
    public function definition()
    {
        return [
            'token' => Str::random(40),
            'role_id' => null,
            'used_at' => null,
        ];
    }
}
