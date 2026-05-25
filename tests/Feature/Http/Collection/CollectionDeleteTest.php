<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\Http\Collection;

use App\Models\Collection\Collection;
use App\Models\Role;
use Polis\Tests\DatabaseSetupTrait;
use Polis\Tests\TestCase;
use Polis\Tests\Traits\MocksApplicationLog;
use Polis\Tests\Traits\RolesTesting;

/**
 * Class MembershipPlanDeleteTest
 */
final class CollectionDeleteTest extends TestCase
{
    use DatabaseSetupTrait, MocksApplicationLog, RolesTesting;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();
        $this->mockApplicationLog();
    }

    public function test_not_logged_in_user_blocked(): void
    {
        $model = Collection::factory()->create();
        $response = $this->json('DELETE', '/v1/collections/'.$model->id);
        $response->assertStatus(403);
    }

    public function test_non_admin_user_blocked(): void
    {
        $model = Collection::factory()->create();
        foreach ($this->rolesWithoutAdmins() as $role) {
            $this->actAs($role);
            $response = $this->json('DELETE', '/v1/collections/'.$model->id);
            $response->assertStatus(403);
        }
    }

    public function test_delete_single(): void
    {
        $this->actAsUser();

        $model = Collection::factory()->create([
            'owner_id' => $this->actingAs->id,
        ]);

        $response = $this->json('DELETE', '/v1/collections/'.$model->id);

        $response->assertStatus(204);
        $this->assertEquals(0, Collection::count());
    }

    public function test_delete_single_invalid_id_fails(): void
    {
        $this->actAs(Role::SUPER_ADMIN);

        $response = $this->json('DELETE', '/v1/collections/a')
            ->assertExactJson([
                'message' => 'This path was not found.',
            ]);
        $response->assertStatus(404);
    }

    public function test_delete_single_not_found_fails(): void
    {
        $this->actAs(Role::SUPER_ADMIN);

        $response = $this->json('DELETE', '/v1/collections/1')
            ->assertExactJson([
                'message' => 'This item was not found.',
            ]);
        $response->assertStatus(404);
    }
}
