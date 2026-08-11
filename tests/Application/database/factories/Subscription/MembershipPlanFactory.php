<?php
declare(strict_types=1);

namespace Database\Factories\Subscription;

use App\Models\Subscription\MembershipPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Class MembershipPlanFactory
 * @package Database\Factories\Subscription
 */
class MembershipPlanFactory extends Factory
{
    /**
     * @var string The related model
     */
    protected $model = MembershipPlan::class;

    /**
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->name,
            'duration' => MembershipPlan::DURATION_YEAR,
        ];
    }
}
