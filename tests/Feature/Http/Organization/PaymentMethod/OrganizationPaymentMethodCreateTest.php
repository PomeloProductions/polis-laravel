<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\Http\Organization\PaymentMethod;

use App\Models\Organization\Organization;
use App\Models\Organization\OrganizationManager;
use App\Models\Payment\PaymentMethod;
use App\Models\Role;
use Polis\Contracts\Services\StripeCustomerServiceContract;
use Polis\Tests\CustomMockInterface;
use Polis\Tests\DatabaseSetupTrait;
use Polis\Tests\TestCase;
use Polis\Tests\Traits\MocksApplicationLog;

/**
 * Class OrganizationPaymentMethodCreateTest
 */
final class OrganizationPaymentMethodCreateTest extends TestCase
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

        $this->path .= $this->organization->id.'/payment-methods';
    }

    public function test_not_logged_in_organization_blocked(): void
    {
        $response = $this->json('POST', $this->path);

        $response->assertStatus(403);
    }

    public function test_incorrect_user_blocked(): void
    {
        $this->actAsUser();
        $response = $this->json('POST', $this->path);

        $response->assertStatus(403);
    }

    public function test_create_successful(): void
    {
        $this->actAsUser();
        OrganizationManager::factory()->create([
            'user_id' => $this->actingAs->id,
            'organization_id' => $this->organization->id,
            'role_id' => Role::ADMINISTRATOR,
        ]);

        /** @var StripeCustomerServiceContract|CustomMockInterface $stripeCustomerService */
        $stripeCustomerService = $this->mock(StripeCustomerServiceContract::class);

        $this->app->bind(StripeCustomerServiceContract::class, function () use ($stripeCustomerService) {
            return $stripeCustomerService;
        });

        $stripeCustomerService->shouldReceive('createPaymentMethod')->once()
            ->with(\Mockery::on(function (Organization $organization) {
                $this->assertEquals($organization->id, $this->organization->id);

                return true;
            }), 'test_token')->andReturn(new PaymentMethod([
                'payment_method_key' => 'test_key',
                'payment_method_type' => 'test_type',
            ]));

        $response = $this->json('POST', $this->path, [
            'token' => 'test_token',
        ]);

        $response->assertStatus(201);

        $response->assertJson([
            'payment_method_key' => 'test_key',
            'payment_method_type' => 'test_type',
        ]);
    }

    public function test_create_fails_required_fields_not_present(): void
    {
        $this->actAsUser();
        OrganizationManager::factory()->create([
            'user_id' => $this->actingAs->id,
            'organization_id' => $this->organization->id,
            'role_id' => Role::ADMINISTRATOR,
        ]);

        $response = $this->json('POST', $this->path);

        $response->assertStatus(400);

        $response->assertJson([
            'errors' => [
                'token' => ['The token field is required.'],
            ],
        ]);
    }

    public function test_create_fails_invalid_string_fields(): void
    {
        $this->actAsUser();
        OrganizationManager::factory()->create([
            'user_id' => $this->actingAs->id,
            'organization_id' => $this->organization->id,
            'role_id' => Role::ADMINISTRATOR,
        ]);

        $response = $this->json('POST', $this->path, [
            'token' => 1,
            'brand' => 1,
        ]);

        $response->assertStatus(400);

        $response->assertJson([
            'errors' => [
                'token' => ['The token must be a string.'],
            ],
        ]);
    }

    public function test_create_fails_invalid_boolean_fields(): void
    {
        $this->actAsUser();
        OrganizationManager::factory()->create([
            'user_id' => $this->actingAs->id,
            'organization_id' => $this->organization->id,
            'role_id' => Role::ADMINISTRATOR,
        ]);

        $response = $this->json('POST', $this->path, [
            'default' => 'hello',
        ]);

        $response->assertStatus(400);

        $response->assertJson([
            'errors' => [
                'default' => ['The default field must be true or false.'],
            ],
        ]);
    }

    public function test_create_fails_strings_too_long(): void
    {
        $this->actAsUser();
        OrganizationManager::factory()->create([
            'user_id' => $this->actingAs->id,
            'organization_id' => $this->organization->id,
            'role_id' => Role::ADMINISTRATOR,
        ]);

        $response = $this->json('POST', $this->path, [
            'token' => str_repeat('a', 121),
        ]);

        $response->assertStatus(400);

        $response->assertJson([
            'errors' => [
                'token' => ['The token may not be greater than 120 characters.'],
            ],
        ]);
    }
}
