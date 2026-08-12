<?php
declare(strict_types=1);

namespace Database\Factories\Wiki;

use App\Models\Wiki\Article;
use App\Models\Wiki\ArticleModification;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Class ArticleModificationFactory
 * @package Database\Factories\Wiki
 */
class ArticleModificationFactory extends Factory
{
    /**
     * @var string The related model
     */
    protected $model = ArticleModification::class;

    /**
     * @return array
     */
    public function definition()
    {
        return [
            'content' => $this->faker->text,
            'action' => 'add',
            'start_position' => 0,
            'article_id' => Article::factory()->create()->id,
        ];
    }
}
