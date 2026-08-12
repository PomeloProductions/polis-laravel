<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\Http\Organization\PaymentMethod;

use App\Models\Organization\Organization;
use App\Models\Organization\OrganizationManager;
use App\Models\Payment\PaymentMethod;
use App\Models\Role;
use Polis\Tests\Application\ApplicationTestCase;
use Polis\Tests\Traits\MocksApplicationLog;

/**
 * Class OrganizationPaymentMethodDeleteTest
 */
final class OrganizationPaymentMethodDeleteTest extends ApplicationTestCase
{
    use MocksApplicationLog;

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
        $response = $this->json('DELETE', $this->path.$paymentMethod->id);

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

        $response = $this->json('DELETE', $this->path.$paymentMethod->id);

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

        $response = $this->json('DELETE', $this->path.$paymentMethod->id);

        $response->assertStatus(403);
    }

    public function test_delete_successful(): void
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

        $response = $this->json('DELETE', $this->path.$paymentMethod->id);

        $response->assertStatus(204);

        $this->assertCount(0, PaymentMethod::all());
    }
}
