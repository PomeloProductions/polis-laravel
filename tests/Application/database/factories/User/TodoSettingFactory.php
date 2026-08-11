<?php

declare(strict_types=1);

namespace Database\Factories\User;

use App\Models\User\TodoSetting;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TodoSettingFactory extends Factory
{
    protected $model = TodoSetting::class;

    public function definition()
    {
        return [
            'user_id' => User::factory()->create()->id,
            'week_start_day' => 0,
        ];
    }

    public function mondayStart(): Factory
    {
        return $this->state(fn () => ['week_start_day' => 1]);
    }
}
