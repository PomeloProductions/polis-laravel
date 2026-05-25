<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\Http\Organization\Asset;

use App\Models\Asset;
use App\Models\Organization\Organization;
use App\Models\Organization\OrganizationManager;
use App\Models\Role;
use Polis\Tests\DatabaseSetupTrait;
use Polis\Tests\TestCase;
use Polis\Tests\Traits\MocksApplicationLog;

/**
 * Class OrganizationAssetUpdateTest
 */
final class OrganizationAssetUpdateTest extends TestCase
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

        $this->path .= $this->organization->id.'/assets/';
    }

    public function test_not_logged_in_organization_blocked(): void
    {
        $asset = Asset::factory()->create([
            'owner_id' => $this->organization->id,
            'owner_type' => 'organization',
        ]);
        $response = $this->json('PATCH', $this->path.$asset->id);

        $response->assertStatus(403);
    }

    public function test_not_related_to_organization_blocked(): void
    {
        $this->actAs(Role::APP_USER);
        $asset = Asset::factory()->create([
            'owner_id' => $this->organization->id,
            'owner_type' => 'organization',
        ]);
        $response = $this->json('PATCH', $this->path.$asset->id);

        $response->assertStatus(403);
    }

    public function test_different_organization_than_asset_blocked(): void
    {
        $this->actAs(Role::APP_USER);
        OrganizationManager::factory()->create([
            'organization_id' => $this->organization->id,
            'role_id' => Role::MANAGER,
            'user_id' => $this->actingAs->id,
        ]);
        $asset = Asset::factory()->create();
        $response = $this->json('PATCH', $this->path.$asset->id);

        $response->assertStatus(403);
    }

    public function test_update_successful(): void
    {
        $this->actAs(Role::APP_USER);
        OrganizationManager::factory()->create([
            'organization_id' => $this->organization->id,
            'role_id' => Role::MANAGER,
            'user_id' => $this->actingAs->id,
        ]);
        $asset = Asset::factory()->create([
            'owner_id' => $this->organization->id,
            'owner_type' => 'organization',
            'name' => 'A Name',
        ]);
        $response = $this->json('PATCH', $this->path.$asset->id, [
            'name' => 'A New Name',
        ]);

        $response->assertStatus(200);
        /** @var Asset $updated */
        $updated = Asset::find($asset->id);
        $this->assertEquals('A New Name', $updated->name);
    }

    public function test_fails_not_present_fields_present(): void
    {
        $this->actAs(Role::APP_USER);
        OrganizationManager::factory()->create([
            'organization_id' => $this->organization->id,
            'role_id' => Role::MANAGER,
            'user_id' => $this->actingAs->id,
        ]);
        $asset = Asset::factory()->create([
            'owner_id' => $this->organization->id,
            'owner_type' => 'organization',
        ]);
        $response = $this->json('PATCH', $this->path.$asset->id, [
            'file_contents' => 'regoijer',
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'errors' => [
                'file_contents' => ['The file contents field is not allowed or can not be set for this request.'],
            ],
        ]);
    }

    public function test_fails_invalid_string_fields(): void
    {
        $this->actAs(Role::APP_USER);
        OrganizationManager::factory()->create([
            'organization_id' => $this->organization->id,
            'role_id' => Role::MANAGER,
            'user_id' => $this->actingAs->id,
        ]);
        $asset = Asset::factory()->create([
            'owner_id' => $this->organization->id,
            'owner_type' => 'organization',
        ]);
        $response = $this->json('PATCH', $this->path.$asset->id, [
            'name' => 45,
            'caption' => 45,
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'errors' => [
                'name' => ['The name must be a string.'],
                'caption' => ['The caption must be a string.'],
            ],
        ]);
    }
}
