<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\Http\Organization;

use App\Models\Organization\Organization;
use App\Models\Organization\OrganizationManager;
use App\Models\Role;
use Polis\Tests\Application\ApplicationTestCase;
use Polis\Tests\Traits\MocksApplicationLog;
use Polis\Tests\Traits\RolesTesting;

/**
 * Class OrganizationUpdateTest
 */
final class OrganizationUpdateTest extends ApplicationTestCase
{
    use MocksApplicationLog, RolesTesting;

    const BASE_ROUTE = '/v1/organizations/';

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();
        $this->mockApplicationLog();
    }

    public function test_not_logged_in_user_blocked(): void
    {
        $membershipPlan = Organization::factory()->create();
        $response = $this->json('PATCH', self::BASE_ROUTE.$membershipPlan->id);
        $response->assertStatus(403);
    }

    public function test_not_admin_user_blocked(): void
    {
        foreach ($this->rolesWithoutAdmins() as $role) {
            $this->actAs($role);
            $model = Organization::factory()->create();
            $response = $this->json('PATCH', self::BASE_ROUTE.$model->id);
            $response->assertStatus(403);
        }
    }

    public function test_patch_successful(): void
    {
        $this->actAs(Role::ADMINISTRATOR);

        /** @var Organization $organization */
        $organization = Organization::factory()->create([
            'name' => 'Test Organiz',
        ]);
        OrganizationManager::factory()->create([
            'user_id' => $this->actingAs->id,
            'role_id' => Role::ADMINISTRATOR,
            'organization_id' => $organization->id,
        ]);

        $data = [
            'name' => 'Test Organization',
        ];

        $response = $this->json('PATCH', self::BASE_ROUTE.$organization->id, $data);
        $response->assertStatus(200);
        $response->assertJson($data);

        /** @var Organization $updated */
        $updated = Organization::find($organization->id);

        $this->assertEquals('Test Organization', $updated->name);
    }

    public function test_patch_not_found_fails(): void
    {
        $this->actAs(Role::SUPER_ADMIN);

        $response = $this->json('PATCH', self::BASE_ROUTE.'5')
            ->assertSimilarJson([
                'message' => 'This item was not found.',
            ]);
        $response->assertStatus(404);
    }

    public function test_patch_invalid_id_fails(): void
    {
        $this->actAs(Role::SUPER_ADMIN);

        $response = $this->json('PATCH', self::BASE_ROUTE.'/b')
            ->assertSimilarJson([
                'message' => 'This path was not found.',
            ]);
        $response->assertStatus(404);
    }

    public function test_patch_fails_invalid_string_fields(): void
    {
        $organization = Organization::factory()->create();

        $this->actAs(Role::SUPER_ADMIN);

        $data = [
            'name' => 5,
        ];

        $response = $this->json('PATCH', self::BASE_ROUTE.$organization->id, $data);

        $response->assertStatus(400);
        $response->assertJson([
            'message' => 'Sorry, something went wrong.',
            'errors' => [
                'name' => ['The name must be a string.'],
            ],
        ]);
    }

    public function test_patch_fails_too_long_fields(): void
    {
        $organization = Organization::factory()->create();

        $this->actAs(Role::SUPER_ADMIN);

        $data = [
            'name' => str_repeat('a', 121),
        ];

        $response = $this->json('PATCH', self::BASE_ROUTE.$organization->id, $data);

        $response->assertStatus(400);
        $response->assertJson([
            'message' => 'Sorry, something went wrong.',
            'errors' => [
                'name' => ['The name may not be greater than 120 characters.'],
            ],
        ]);
    }
}
