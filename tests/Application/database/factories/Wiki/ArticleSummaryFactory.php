<?php
declare(strict_types=1);

namespace Database\Factories\Wiki;

use App\Models\Wiki\Article;
use App\Models\Wiki\ArticleSummary;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Class ArticleSummaryFactory
 * @package Database\Factories\Wiki
 */
class ArticleSummaryFactory extends Factory
{
    /**
     * @var string The related model
     */
    protected $model = ArticleSummary::class;

    /**
     * @return array
     */
    public function definition()
    {
        return [
            'article_id' => Article::factory()->create()->id,
            'content' => $this->faker->paragraph(),
        ];
    }
}
