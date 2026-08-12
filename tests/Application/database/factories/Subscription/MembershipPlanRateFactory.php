<?php
declare(strict_types=1);

namespace Database\Factories\Subscription;

use App\Models\Subscription\MembershipPlan;
use App\Models\Subscription\MembershipPlanRate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Class MembershipPlanRateFactory
 * @package Database\Factories\Subscription
 */
class MembershipPlanRateFactory extends Factory
{
    /**
     * @var string The related model
     */
    protected $model = MembershipPlanRate::class;

    /**
     * @return array
     */
    public function definition()
    {
        return [
            'membership_plan_id' => MembershipPlan::factory()->create()->id,
            'cost' => 10.00,
            'active' => 0,
        ];
    }
}
