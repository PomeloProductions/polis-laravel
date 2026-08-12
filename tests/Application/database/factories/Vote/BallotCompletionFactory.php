<?php
namespace Database\Factories\Vote;

use App\Models\User\User;
use App\Models\Vote\Ballot;
use App\Models\Vote\BallotCompletion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Class AssetFactory
 * @package Database\Factories
 */
class BallotCompletionFactory extends Factory
{
    /**
     * @var string The related model
     */
    protected $model = BallotCompletion::class;

    /**
     * @return array
     */
    public function definition()
    {
        return [
            'ballot_id' => Ballot::factory()->create()->id,
            'user_id' => User::factory()->create()->id,
        ];
    }
}
