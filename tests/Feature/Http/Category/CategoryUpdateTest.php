<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\Http\Category;

use App\Models\Category;
use App\Models\Role;
use Polis\Tests\Application\ApplicationTestCase;
use Polis\Tests\Traits\MocksApplicationLog;
use Polis\Tests\Traits\RolesTesting;

/**
 * Class MembershipPlanUpdateTest
 */
final class CategoryUpdateTest extends ApplicationTestCase
{
    use MocksApplicationLog, RolesTesting;

    const BASE_ROUTE = '/v1/categories/';

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();
        $this->mockApplicationLog();
    }

    public function test_not_logged_in_user_blocked(): void
    {
        $category = Category::factory()->create();
        $response = $this->json('PATCH', self::BASE_ROUTE.$category->id);
        $response->assertStatus(403);
    }

    public function test_not_admin_user_blocked(): void
    {
        foreach ($this->rolesWithoutAdmins() as $role) {
            $this->actAs($role);
            $category = Category::factory()->create();
            $response = $this->json('PATCH', self::BASE_ROUTE.$category->id);
            $response->assertStatus(403);
        }
    }

    public function test_patch_successful(): void
    {
        $this->actAs(Role::SUPER_ADMIN);

        /** @var Category $category */
        $category = Category::factory()->create([
            'name' => 'An Category',
        ]);

        $data = [
            'name' => 'A Category',
        ];

        $response = $this->json('PATCH', self::BASE_ROUTE.$category->id, $data);
        $response->assertStatus(200);
        $response->assertJson($data);

        /** @var Category $updated */
        $updated = Category::find($category->id);

        $this->assertEquals('A Category', $updated->name);
    }

    public function test_patch_not_found_fails(): void
    {
        $this->actAs(Role::SUPER_ADMIN);

        $response = $this->json('PATCH', self::BASE_ROUTE.'5')
            ->assertExactJson([
                'message' => 'This item was not found.',
            ]);
        $response->assertStatus(404);
    }

    public function test_patch_invalid_id_fails(): void
    {
        $this->actAs(Role::SUPER_ADMIN);

        $response = $this->json('PATCH', self::BASE_ROUTE.'/b')
            ->assertExactJson([
                'message' => 'This path was not found.',
            ]);
        $response->assertStatus(404);
    }

    public function test_patch_fails_invalid_string_fields(): void
    {
        $category = Category::factory()->create();

        $this->actAs(Role::SUPER_ADMIN);

        $data = [
            'name' => 5,
            'description' => 5,
        ];

        $response = $this->json('PATCH', self::BASE_ROUTE.$category->id, $data);

        $response->assertStatus(400);
        $response->assertJson([
            'message' => 'Sorry, something went wrong.',
            'errors' => [
                'name' => ['The name must be a string.'],
                'description' => ['The description must be a string.'],
            ],
        ]);
    }
}
