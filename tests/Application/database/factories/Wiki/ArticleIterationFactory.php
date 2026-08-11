<?php
declare(strict_types=1);

namespace Database\Factories\Wiki;

use App\Models\User\User;
use App\Models\Wiki\Article;
use App\Models\Wiki\ArticleIteration;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Class IterationFactory
 * @package Database\Factories\Wiki
 */
class ArticleIterationFactory extends Factory
{
    /**
     * @var string The related model
     */
    protected $model = ArticleIteration::class;

    /**
     * @return array
     */
    public function definition()
    {
        return [
            'content' => $this->faker->text,
            'article_id' => Article::factory()->create()->id,
            'created_by_id' => User::factory()->create()->id,
        ];
    }
}
