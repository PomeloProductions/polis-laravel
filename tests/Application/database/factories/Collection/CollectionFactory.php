<?php
declare(strict_types=1);

namespace Database\Factories\Collection;

use App\Models\Collection\Collection;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CollectionFactory extends Factory
{
    /**
     * @var string The related model
     */
    protected $model = Collection::class;

    /**
     * @return array
     */
    public function definition()
    {
        return [
            'owner_id' => User::factory()->create()->id,
            'owner_type' => 'user',
        ];
    }
}