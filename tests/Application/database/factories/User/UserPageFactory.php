<?php

declare(strict_types=1);

namespace Database\Factories\User;

use App\Models\User\User;
use App\Models\User\UserPage;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserPageFactory extends Factory
{
    protected $model = UserPage::class;

    public function definition()
    {
        return [
            'user_id' => User::factory()->create()->id,
            'slug' => $this->faker->unique()->slug(2),
            'name' => $this->faker->words(2, true),
            'icon' => 'IconList',
            'color' => null,
            'route_path' => $this->faker->slug(2),
            'page_type' => 'list',
            'display_order' => 0,
            'is_visible' => true,
            'is_required' => false,
            'is_nav_item' => true,
            'parent_page_id' => null,
            'config_json' => null,
        ];
    }

    public function required(): Factory
    {
        return $this->state(fn () => ['is_required' => true]);
    }

    public function hidden(): Factory
    {
        return $this->state(fn () => ['is_visible' => false]);
    }

    public function detail(): Factory
    {
        return $this->state(fn () => [
            'page_type' => 'detail',
            'is_nav_item' => false,
        ]);
    }
}
