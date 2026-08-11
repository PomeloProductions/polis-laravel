<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\Application;

use App\Models\Organization\Organization;
use App\Models\Organization\OrganizationManager;
use App\Models\Role;
use Polis\Tests\Application\ApplicationTestCase;

final class OrganizationManagerIndexProbeTest extends ApplicationTestCase
{
    public function test_boot_and_super_admin_sees_index(): void
    {
        $this->actAs(Role::SUPER_ADMIN);
        $organization = Organization::factory()->create();
        OrganizationManager::factory()->count(3)->create([
            'organization_id' => $organization->id,
        ]);

        $response = $this->json('GET', '/v1/organizations/'.$organization->id.'/organization-managers');
        $response->assertStatus(200);
    }
}
