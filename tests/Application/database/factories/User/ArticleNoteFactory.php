<?php
declare(strict_types=1);

namespace Database\Factories\User;

use App\Models\User\ArticleNote;
use App\Models\User\User;
use App\Models\Wiki\Article;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Class ArticleNoteFactory
 * @package Database\Factories\User
 */
class ArticleNoteFactory extends Factory
{
    /**
     * @var string The related model
     */
    protected $model = ArticleNote::class;

    /**
     * @return array
     */
    public function definition()
    {
        return [
            'user_id' => User::factory()->create()->id,
            'article_id' => Article::factory()->create()->id,
            'response' => $this->faker->optional()->text(),
            'completed_at' => $this->faker->optional()->dateTime(),
        ];
    }
}
