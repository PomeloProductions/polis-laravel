<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\Http\Organization\Asset;

use App\Models\Asset;
use App\Models\Organization\Organization;
use App\Models\Organization\OrganizationManager;
use Polis\Tests\Application\ApplicationTestCase;
use Polis\Tests\Traits\MocksApplicationLog;

/**
 * Class UserPaymentMethodDeleteTest
 */
final class OrganizationAssetDeleteTest extends ApplicationTestCase
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
        $this->path .= $this->organization->id.'/assets/';
    }

    public function test_not_logged_in_user_blocked(): void
    {
        $asset = Asset::factory()->create([
            'owner_id' => $this->organization->id,
            'owner_type' => 'organization',
        ]);
        $response = $this->json('DELETE', $this->path.$asset->id);

        $response->assertStatus(403);
    }

    public function test_incorrect_user_blocked(): void
    {
        $asset = Asset::factory()->create([
            'owner_id' => $this->organization->id,
            'owner_type' => 'organization',
        ]);

        $this->actAsUser();

        $response = $this->json('DELETE', $this->path.$asset->id);

        $response->assertStatus(403);
    }

    public function test_delete_successful(): void
    {
        $asset = Asset::factory()->create([
            'owner_id' => $this->organization->id,
            'owner_type' => 'organization',
        ]);

        $this->actAsUser();

        OrganizationManager::factory()->create([
            'user_id' => $this->actingAs->id,
            'organization_id' => $this->organization->id,
        ]);

        $response = $this->json('DELETE', $this->path.$asset->id);

        $response->assertStatus(204);

        $this->assertCount(0, Asset::all());
    }
}
