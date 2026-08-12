<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\V1\CollectionItem;

use App\Models\Collection\Collection;
use App\Models\Collection\CollectionItem;
use App\Models\Role;
use Polis\Tests\Application\ApplicationTestCase;
use Polis\Tests\Traits\MocksApplicationLog;
use Polis\Tests\Traits\RolesTesting;

/**
 * Class MembershipPlanDeleteTest
 */
final class CollectionItemDeleteTest extends ApplicationTestCase
{
    use MocksApplicationLog, RolesTesting;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();
        $this->mockApplicationLog();
    }

    public function test_not_logged_in_user_blocked(): void
    {
        $model = CollectionItem::factory()->create();
        $response = $this->json('DELETE', '/v1/collection-items/'.$model->id);
        $response->assertStatus(403);
    }

    public function test_non_admin_user_blocked(): void
    {
        $model = CollectionItem::factory()->create();
        foreach ($this->rolesWithoutAdmins() as $role) {
            $this->actAs($role);
            $response = $this->json('DELETE', '/v1/collection-items/'.$model->id);
            $response->assertStatus(403);
        }
    }

    public function test_delete_single(): void
    {
        $this->actAsUser();

        $model = CollectionItem::factory()->create([
            'collection_id' => Collection::factory()->create([
                'owner_id' => $this->actingAs->id,
            ])->id,
        ]);

        $response = $this->json('DELETE', '/v1/collection-items/'.$model->id);

        $response->assertStatus(204);
        $this->assertEquals(0, CollectionItem::count());
    }

    public function test_delete_single_invalid_id_fails(): void
    {
        $this->actAs(Role::SUPER_ADMIN);

        $response = $this->json('DELETE', '/v1/collection-items/a')
            ->assertExactJson([
                'message' => 'This path was not found.',
            ]);
        $response->assertStatus(404);
    }

    public function test_delete_single_not_found_fails(): void
    {
        $this->actAs(Role::SUPER_ADMIN);

        $response = $this->json('DELETE', '/v1/collection-items/1')
            ->assertExactJson([
                'message' => 'This item was not found.',
            ]);
        $response->assertStatus(404);
    }
}
