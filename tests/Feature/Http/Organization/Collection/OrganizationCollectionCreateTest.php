<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\Http\Organization\Collection;

use App\Models\Organization\Organization;
use App\Models\Organization\OrganizationManager;
use Polis\Tests\Application\ApplicationTestCase;
use Polis\Tests\Traits\MocksApplicationLog;

/**
 * Class UserPaymentMethodCreateTest
 */
final class OrganizationCollectionCreateTest extends ApplicationTestCase
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

        $this->path .= $this->organization->id.'/collections';
    }

    public function test_not_logged_in_user_blocked(): void
    {
        $response = $this->json('POST', $this->path);

        $response->assertStatus(403);
    }

    public function test_not_part_of_organization_user_role_blocked(): void
    {
        $this->actAsUser();
        $response = $this->json('POST', $this->path);

        $response->assertStatus(403);
    }

    public function test_create_successful(): void
    {
        $this->actAsUser();

        OrganizationManager::factory()->create([
            'organization_id' => $this->organization->id,
            'user_id' => $this->actingAs->id,
        ]);

        $data = [
            'name' => 'My Collection',
            'is_public' => false,
        ];
        $response = $this->json('POST', $this->path, $data);

        $response->assertStatus(201);

        $response->assertJson($data);
    }

    public function test_create_fails_required_fields_not_present(): void
    {
        $this->actAsUser();

        OrganizationManager::factory()->create([
            'organization_id' => $this->organization->id,
            'user_id' => $this->actingAs->id,
        ]);

        $response = $this->json('POST', $this->path);

        $response->assertStatus(422);

        $response->assertJson([
            'errors' => [
                'is_public' => ['The is public field is required.'],
            ],
        ]);
    }

    public function test_create_fails_invalid_string_fields(): void
    {
        $this->actAsUser();

        OrganizationManager::factory()->create([
            'organization_id' => $this->organization->id,
            'user_id' => $this->actingAs->id,
        ]);

        $response = $this->json('POST', $this->path, [
            'name' => 1,
        ]);

        $response->assertStatus(422);

        $response->assertJson([
            'errors' => [
                'name' => ['The name must be a string.'],
            ],
        ]);
    }

    public function test_create_fails_invalid_boolean_fields(): void
    {
        $this->actAsUser();

        OrganizationManager::factory()->create([
            'organization_id' => $this->organization->id,
            'user_id' => $this->actingAs->id,
        ]);

        $response = $this->json('POST', $this->path, [
            'is_public' => 'hello',
        ]);

        $response->assertStatus(422);

        $response->assertJson([
            'errors' => [
                'is_public' => ['The is public field must be true or false.'],
            ],
        ]);
    }
}
