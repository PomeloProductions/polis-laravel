<?php
declare(strict_types=1);

namespace Database\Factories\User;

use App\Models\User\Contact;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Class ContactFactory
 * @package Database\Factories\User
 */
class ContactFactory extends Factory
{
    /**
     * @var string The related model
     */
    protected $model = Contact::class;

    /**
     * @return array
     */
    public function definition()
    {
        return [
            'initiated_by_id' => User::factory()->create()->id,
            'requested_id' => User::factory()->create()->id,
        ];
    }
}
