<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\Http\User\Subscription;

use App\Models\Payment\PaymentMethod;
use App\Models\Subscription\MembershipPlanRate;
use App\Models\Subscription\Subscription;
use App\Models\User\User;
use Polis\Contracts\Services\StripePaymentServiceContract;
use Polis\Tests\DatabaseSetupTrait;
use Polis\Tests\TestCase;
use Polis\Tests\Traits\MocksApplicationLog;

/**
 * Class UserSubscriptionCreateTest
 */
final class UserSubscriptionCreateTest extends TestCase
{
    use DatabaseSetupTrait, MocksApplicationLog;

    /**
     * @var string
     */
    private $path = '/v1/users/';

    /**
     * @var User
     */
    private $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();
        $this->mockApplicationLog();

        $this->user = User::factory()->create();

        $this->path .= $this->user->id.'/subscriptions';
    }

    public function test_not_logged_in_user_blocked(): void
    {
        $response = $this->json('POST', $this->path);

        $response->assertStatus(403);
    }

    public function test_create_successful(): void
    {
        $this->actingAs($this->user);

        $this->app->bind(StripePaymentServiceContract::class, function () {
            $mock = mock(StripePaymentServiceContract::class);

            $mock->shouldReceive('createPayment')->once();

            return $mock;
        });

        $membershipPlanRate = MembershipPlanRate::factory()->create([
            'active' => true,
        ]);
        $paymentMethod = PaymentMethod::factory()->create([
            'owner_id' => $this->user->id,
        ]);

        $response = $this->json('POST', $this->path, [
            'membership_plan_rate_id' => $membershipPlanRate->id,
            'payment_method_id' => $paymentMethod->id,
        ]);

        $response->assertStatus(201);

        /** @var Subscription $subscription */
        $subscription = Subscription::first();

        $this->assertEquals($subscription->membership_plan_rate_id, $membershipPlanRate->id);
        $this->assertEquals($subscription->payment_method_id, $paymentMethod->id);
        $this->assertEquals($subscription->subscriber_id, $this->user->id);
    }

    public function test_create_fails_when_stripe_fails(): void
    {
        $this->actingAs($this->user);

        $this->app->bind(StripePaymentServiceContract::class, function () {
            $mock = mock(StripePaymentServiceContract::class);

            $mock->shouldReceive('createPayment')->once()->andThrow(new \Exception);

            return $mock;
        });

        $membershipPlanRate = MembershipPlanRate::factory()->create([
            'active' => true,
        ]);
        $paymentMethod = PaymentMethod::factory()->create([
            'owner_id' => $this->user->id,
        ]);

        $response = $this->json('POST', $this->path, [
            'membership_plan_rate_id' => $membershipPlanRate->id,
            'payment_method_id' => $paymentMethod->id,
        ]);

        $response->assertStatus(503);
        $response->assertJson([
            'message' => 'Unable to accept payments right now',
        ]);

        $this->assertNull(Subscription::first());
    }

    public function test_create_fails_without_required_fields(): void
    {
        $this->actingAs($this->user);

        $response = $this->json('POST', $this->path);

        $response->assertStatus(400);
        $response->assertJson([
            'errors' => [
                'membership_plan_rate_id' => ['The membership plan rate id field is required.'],
                'payment_method_id' => ['The payment method id field is required unless is trial is in true.'],
            ],
        ]);
    }

    public function test_create_fails_with_not_present_fields_present(): void
    {
        $this->actingAs($this->user);

        $response = $this->json('POST', $this->path, [
            'cancel' => true,
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'errors' => [
                'cancel' => ['The cancel field is not allowed or can not be set for this request.'],
            ],
        ]);
    }

    public function test_create_fails_invalid_boolean_field(): void
    {
        $this->actingAs($this->user);

        $response = $this->json('POST', $this->path, [
            'recurring' => 'hello',
            'is_trial' => 'hello',
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'errors' => [
                'recurring' => ['The recurring field must be true or false.'],
                'is_trial' => ['The is trial field must be true or false.'],
            ],
        ]);
    }

    public function test_create_fails_invalid_integer_fields(): void
    {
        $this->actingAs($this->user);

        $response = $this->json('POST', $this->path, [
            'membership_plan_rate_id' => 'hi',
            'payment_method_id' => 'hi',
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'errors' => [
                'membership_plan_rate_id' => ['The membership plan rate id must be an integer.'],
                'payment_method_id' => ['The payment method id must be an integer.'],
            ],
        ]);
    }

    public function test_create_fails_invalid_model_fields(): void
    {
        $this->actingAs($this->user);

        $response = $this->json('POST', $this->path, [
            'membership_plan_rate_id' => 3452,
            'payment_method_id' => 54,
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'errors' => [
                'membership_plan_rate_id' => ['The selected membership plan rate id is invalid.'],
                'payment_method_id' => ['The selected payment method id is invalid.'],
            ],
        ]);
    }

    public function test_create_fails_purchasing_inactive_rate(): void
    {
        $this->actingAs($this->user);

        $membershipPlanRate = MembershipPlanRate::factory()->create([
            'active' => false,
        ]);

        $response = $this->json('POST', $this->path, [
            'membership_plan_rate_id' => $membershipPlanRate->id,
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'errors' => [
                'membership_plan_rate_id' => ['The membership plan rate must be active for you to purchase it.'],
            ],
        ]);
    }

    public function test_create_fails_payment_method_not_owned_by_user(): void
    {
        $this->actingAs($this->user);

        $paymentMethod = PaymentMethod::factory()->create();

        $response = $this->json('POST', $this->path, [
            'payment_method_id' => $paymentMethod->id,
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'errors' => [
                'payment_method_id' => ['This payment method does not belong to this user.'],
            ],
        ]);
    }
}
