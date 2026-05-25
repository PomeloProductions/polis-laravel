<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\Http\Organization\Subscription;

use App\Models\Organization\Organization;
use App\Models\Organization\OrganizationManager;
use App\Models\Payment\PaymentMethod;
use App\Models\Role;
use App\Models\Subscription\Subscription;
use Polis\Tests\DatabaseSetupTrait;
use Polis\Tests\TestCase;
use Polis\Tests\Traits\MocksApplicationLog;

/**
 * Class OrganizationSubscriptionUpdateTest
 */
final class OrganizationSubscriptionUpdateTest extends TestCase
{
    use DatabaseSetupTrait, MocksApplicationLog;

    /**
     * @var string
     */
    private $path = '/v1/organizations/';

    /**
     * @var Organization
     */
    private $organizaion;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();
        $this->mockApplicationLog();

        $this->organizaion = Organization::factory()->create();

        $this->path .= $this->organizaion->id.'/subscriptions/';
    }

    public function test_not_logged_in_user_blocked(): void
    {
        $subscription = Subscription::factory()->create([
            'subscriber_id' => $this->organizaion->id,
        ]);
        $response = $this->json('PATCH', $this->path.$subscription->id);

        $response->assertStatus(403);
    }

    public function test_disconnected_user_blocked(): void
    {
        $this->actAs(Role::APP_USER);
        $subscription = Subscription::factory()->create([
            'subscriber_id' => $this->organizaion->id,
            'subscriber_type' => 'organization',
        ]);
        $response = $this->json('PATCH', $this->path.$subscription->id);

        $response->assertStatus(403);
    }

    public function test_different_user_than_subscription_blocked(): void
    {
        $this->actAs(Role::APP_USER);
        OrganizationManager::factory()->create([
            'role_id' => Role::ADMINISTRATOR,
            'user_id' => $this->actingAs->id,
            'organization_id' => $this->organizaion->id,
        ]);
        $subscription = Subscription::factory()->create();
        $response = $this->json('PATCH', $this->path.$subscription->id);

        $response->assertStatus(403);
    }

    public function test_wrong_role_blocked(): void
    {
        $this->actAs(Role::APP_USER);
        OrganizationManager::factory()->create([
            'role_id' => Role::MANAGER,
            'user_id' => $this->actingAs->id,
            'organization_id' => $this->organizaion->id,
        ]);
        $subscription = Subscription::factory()->create([
            'subscriber_id' => $this->organizaion->id,
            'subscriber_type' => 'organization',
        ]);
        $response = $this->json('PATCH', $this->path.$subscription->id);

        $response->assertStatus(403);
    }

    public function test_update_successful(): void
    {
        $this->actAs(Role::APP_USER);
        OrganizationManager::factory()->create([
            'role_id' => Role::ADMINISTRATOR,
            'user_id' => $this->actingAs->id,
            'organization_id' => $this->organizaion->id,
        ]);
        $subscription = Subscription::factory()->create([
            'subscriber_id' => $this->organizaion->id,
            'subscriber_type' => 'organization',
        ]);
        $response = $this->json('PATCH', $this->path.$subscription->id, [
            'cancel' => true,
        ]);

        $response->assertStatus(200);
        /** @var Subscription $updated */
        $updated = Subscription::find($subscription->id);
        $this->assertNotNull($updated->canceled_at);
    }

    public function test_fails_not_present_fields_present(): void
    {
        $this->actAs(Role::APP_USER);
        OrganizationManager::factory()->create([
            'role_id' => Role::ADMINISTRATOR,
            'user_id' => $this->actingAs->id,
            'organization_id' => $this->organizaion->id,
        ]);
        $subscription = Subscription::factory()->create([
            'subscriber_id' => $this->organizaion->id,
            'subscriber_type' => 'organization',
        ]);
        $response = $this->json('PATCH', $this->path.$subscription->id, [
            'membership_plan_rate_id' => 32,
            'is_trial' => false,
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'errors' => [
                'membership_plan_rate_id' => ['The membership plan rate id field is not allowed or can not be set for this request.'],
                'is_trial' => ['The is trial field is not allowed or can not be set for this request.'],
            ],
        ]);
    }

    public function test_update_fails_invalid_boolean_field(): void
    {
        $this->actAs(Role::APP_USER);
        OrganizationManager::factory()->create([
            'role_id' => Role::ADMINISTRATOR,
            'user_id' => $this->actingAs->id,
            'organization_id' => $this->organizaion->id,
        ]);
        $subscription = Subscription::factory()->create([
            'subscriber_id' => $this->organizaion->id,
            'subscriber_type' => 'organization',
        ]);
        $response = $this->json('PATCH', $this->path.$subscription->id, [
            'recurring' => 'hello',
            'cancel' => 'hello',
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'errors' => [
                'recurring' => ['The recurring field must be true or false.'],
                'cancel' => ['The cancel field must be true or false.'],
            ],
        ]);
    }

    public function test_update_fails_invalid_integer_fields(): void
    {
        $this->actAs(Role::APP_USER);
        OrganizationManager::factory()->create([
            'role_id' => Role::ADMINISTRATOR,
            'user_id' => $this->actingAs->id,
            'organization_id' => $this->organizaion->id,
        ]);
        $subscription = Subscription::factory()->create([
            'subscriber_id' => $this->organizaion->id,
            'subscriber_type' => 'organization',
        ]);
        $response = $this->json('PATCH', $this->path.$subscription->id, [
            'payment_method_id' => 'hi',
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'errors' => [
                'payment_method_id' => ['The payment method id must be an integer.'],
            ],
        ]);
    }

    public function test_update_fails_invalid_model_fields(): void
    {
        $this->actAs(Role::APP_USER);
        OrganizationManager::factory()->create([
            'role_id' => Role::ADMINISTRATOR,
            'user_id' => $this->actingAs->id,
            'organization_id' => $this->organizaion->id,
        ]);
        $subscription = Subscription::factory()->create([
            'subscriber_id' => $this->organizaion->id,
            'subscriber_type' => 'organization',
        ]);
        $response = $this->json('PATCH', $this->path.$subscription->id, [
            'payment_method_id' => 54,
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'errors' => [
                'payment_method_id' => ['The selected payment method id is invalid.'],
            ],
        ]);
    }

    public function test_update_fails_payment_method_not_owned_by_user(): void
    {
        $paymentMethod = PaymentMethod::factory()->create();
        $this->actAs(Role::APP_USER);
        OrganizationManager::factory()->create([
            'role_id' => Role::ADMINISTRATOR,
            'user_id' => $this->actingAs->id,
            'organization_id' => $this->organizaion->id,
        ]);
        $subscription = Subscription::factory()->create([
            'subscriber_id' => $this->organizaion->id,
            'subscriber_type' => 'organization',
        ]);
        $response = $this->json('PATCH', $this->path.$subscription->id, [
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
