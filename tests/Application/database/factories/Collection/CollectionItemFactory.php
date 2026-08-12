<?php
declare(strict_types=1);

namespace Database\Factories\Collection;

use App\Models\Collection\Collection;
use App\Models\Collection\CollectionItem;
use App\Models\Wiki\Article;
use Illuminate\Database\Eloquent\Factories\Factory;

class CollectionItemFactory extends Factory
{
    /**
     * @var string The related model
     */
    protected $model = CollectionItem::class;

    /**
     * @return array
     */
    public function definition()
    {
        return [
            'collection_id' => Collection::factory()->create()->id,
            'item_id' => Article::factory()->create()->id,
            'item_type' => 'article',
            'order' => $this->faker->randomNumber(),
        ];
    }
}