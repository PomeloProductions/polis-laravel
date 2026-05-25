<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\Http\Organization\PaymentMethod;

use App\Models\Organization\Organization;
use App\Models\Organization\OrganizationManager;
use App\Models\Payment\PaymentMethod;
use App\Models\Role;
use Polis\Tests\DatabaseSetupTrait;
use Polis\Tests\TestCase;
use Polis\Tests\Traits\MocksApplicationLog;

/**
 * Class OrganizationPaymentMethodCreateTest
 */
final class OrganizationPaymentMethodUpdateTest extends TestCase
{
    use DatabaseSetupTrait, MocksApplicationLog;

    /**
     * @var string
     */
    private $path = '/v1/organizations/';

    /**
     * @var Organization
     */
    private $organization;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();
        $this->mockApplicationLog();

        $this->organization = Organization::factory()->create();

        $this->path .= $this->organization->id.'/payment-methods/';
    }

    public function test_not_logged_in_organization_blocked(): void
    {
        $paymentMethod = PaymentMethod::factory()->create([
            'owner_id' => $this->organization->id,
            'owner_type' => 'organization',
        ]);
        $response = $this->json('PUT', $this->path.$paymentMethod->id);

        $response->assertStatus(403);
    }

    public function test_not_administrator_blocked(): void
    {
        $paymentMethod = PaymentMethod::factory()->create([
            'owner_id' => $this->organization->id,
            'owner_type' => 'organization',
        ]);

        $this->actAsUser();
        OrganizationManager::factory()->create([
            'organization_id' => $this->organization->id,
            'user_id' => $this->actingAs->id,
            'role_id' => Role::MANAGER,
        ]);

        $response = $this->json('PUT', $this->path.$paymentMethod->id);

        $response->assertStatus(403);
    }

    public function test_organization_does_not_own_payment_method_blocked(): void
    {
        $paymentMethod = PaymentMethod::factory()->create();

        $this->actAsUser();
        OrganizationManager::factory()->create([
            'organization_id' => $this->organization->id,
            'user_id' => $this->actingAs->id,
            'role_id' => Role::ADMINISTRATOR,
        ]);

        $response = $this->json('PUT', $this->path.$paymentMethod->id);

        $response->assertStatus(403);
    }

    public function test_update_successful(): void
    {
        $paymentMethod = PaymentMethod::factory()->create([
            'owner_id' => $this->organization->id,
            'owner_type' => 'organization',
        ]);

        $this->actAsUser();
        OrganizationManager::factory()->create([
            'organization_id' => $this->organization->id,
            'user_id' => $this->actingAs->id,
            'role_id' => Role::ADMINISTRATOR,
        ]);

        $response = $this->json('PUT', $this->path.$paymentMethod->id, [
            'default' => false,
        ]);

        $response->assertStatus(200);

        $response->assertJson([
            'default' => false,
        ]);
    }

    public function test_update_fails_not_allowed_fields_present(): void
    {
        $paymentMethod = PaymentMethod::factory()->create([
            'owner_id' => $this->organization->id,
            'owner_type' => 'organization',
        ]);

        $this->actAsUser();
        OrganizationManager::factory()->create([
            'organization_id' => $this->organization->id,
            'user_id' => $this->actingAs->id,
            'role_id' => Role::ADMINISTRATOR,
        ]);

        $response = $this->json('PUT', $this->path.$paymentMethod->id, [
            'token' => 'hi',
        ]);

        $response->assertStatus(400);

        $response->assertJson([
            'errors' => [
                'token' => ['The token field is not allowed or can not be set for this request.'],
            ],
        ]);
    }

    public function test_create_fails_invalid_boolean_fields(): void
    {
        $paymentMethod = PaymentMethod::factory()->create([
            'owner_id' => $this->organization->id,
            'owner_type' => 'organization',
        ]);

        $this->actAsUser();
        OrganizationManager::factory()->create([
            'organization_id' => $this->organization->id,
            'user_id' => $this->actingAs->id,
            'role_id' => Role::ADMINISTRATOR,
        ]);

        $response = $this->json('PUT', $this->path.$paymentMethod->id, [
            'default' => 'hello',
        ]);

        $response->assertStatus(400);

        $response->assertJson([
            'errors' => [
                'default' => ['The default field must be true or false.'],
            ],
        ]);
    }
}
