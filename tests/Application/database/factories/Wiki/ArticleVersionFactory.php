<?php
declare(strict_types=1);

namespace Database\Factories\Wiki;

use App\Models\Wiki\Article;
use App\Models\Wiki\ArticleVersion;
use App\Models\Wiki\ArticleIteration;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Class ArticleVersionFactory
 * @package Database\Factories\Wiki
 */
class ArticleVersionFactory extends Factory
{
    /**
     * @var string The related model
     */
    protected $model = ArticleVersion::class;

    /**
     * @return array
     */
    public function definition()
    {
        return [
            'article_id' => Article::factory()->create()->id,
            'article_iteration_id' => ArticleIteration::factory()->create()->id,
        ];
    }
}
