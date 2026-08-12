<?php
namespace Database\Factories\Vote;

use App\Models\Vote\Ballot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Class AssetFactory
 * @package Database\Factories
 */
class BallotFactory extends Factory
{
    /**
     * @var string The related model
     */
    protected $model = Ballot::class;

    /**
     * @return array
     */
    public function definition()
    {
        return [
            'type' => Ballot::TYPE_SINGLE_OPTION,
        ];
    }
}
