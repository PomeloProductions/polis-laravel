<?php
declare(strict_types=1);

namespace Database\Factories\Subscription;

use App\Models\Payment\PaymentMethod;
use App\Models\Subscription\MembershipPlanRate;
use App\Models\Subscription\Subscription;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Class SubscriptionFactory
 * @package Database\Factories\Subscription
 */
class SubscriptionFactory extends Factory
{
    /**
     * @var string The related model
     */
    protected $model = Subscription::class;

    /**
     * @return array
     */
    public function definition()
    {
        return [
            'membership_plan_rate_id' => MembershipPlanRate::factory()->create()->id,
            'payment_method_id' => PaymentMethod::factory()->create()->id,
            'subscriber_id' => User::factory()->create()->id,
            'subscriber_type' => 'user',
        ];
    }
}
