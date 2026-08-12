<?php
namespace Database\Factories\Vote;

use App\Models\Vote\Ballot;
use App\Models\Vote\BallotItem;
use App\Models\Wiki\ArticleIteration;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Class AssetFactory
 * @package Database\Factories
 */
class BallotItemFactory extends Factory
{
    /**
     * @var string The related model
     */
    protected $model = BallotItem::class;

    /**
     * @return array
     */
    public function definition()
    {
        return [
            'ballot_id' => Ballot::factory()->create()->id,
        ];
    }
}
