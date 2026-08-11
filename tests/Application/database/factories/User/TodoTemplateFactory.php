<?php

declare(strict_types=1);

namespace Database\Factories\User;

use App\Models\User\TodoTemplate;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TodoTemplateFactory extends Factory
{
    protected $model = TodoTemplate::class;

    public function definition()
    {
        return [
            'user_id' => User::factory()->create()->id,
            'name' => $this->faker->words(2, true),
            'level' => $this->faker->randomElement(['year', 'month', 'week', 'day']),
            'sections_json' => [
                [
                    'key' => 'section-1',
                    'label' => 'Default Section',
                    'type' => 'todo_bullet_list',
                    'config' => [],
                ],
            ],
        ];
    }

    public function day(): Factory
    {
        return $this->state(fn () => ['level' => 'day']);
    }

    public function week(): Factory
    {
        return $this->state(fn () => ['level' => 'week']);
    }

    public function month(): Factory
    {
        return $this->state(fn () => ['level' => 'month']);
    }

    public function year(): Factory
    {
        return $this->state(fn () => ['level' => 'year']);
    }
}
