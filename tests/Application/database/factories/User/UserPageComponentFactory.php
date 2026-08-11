<?php

declare(strict_types=1);

namespace Database\Factories\User;

use App\Models\User\UserPage;
use App\Models\User\UserPageComponent;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserPageComponentFactory extends Factory
{
    protected $model = UserPageComponent::class;

    public function definition()
    {
        return [
            'user_page_id' => UserPage::factory()->create()->id,
            'component_type' => 'stats_cards',
            'display_order' => 0,
            'config_json' => null,
        ];
    }
}
