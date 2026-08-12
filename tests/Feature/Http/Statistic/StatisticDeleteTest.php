<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\Http\Statistic;

use App\Models\Role;
use App\Models\Statistic\Statistic;
use Polis\Tests\Application\ApplicationTestCase;
use Polis\Tests\Traits\MocksApplicationLog;
use Polis\Tests\Traits\RolesTesting;

/**
 * Class StatisticDeleteTest
 */
class StatisticDeleteTest extends ApplicationTestCase
{
    use MocksApplicationLog, RolesTesting;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();
        $this->mockApplicationLog();
    }

    public function test_not_logged_in_user_blocked()
    {
        $model = Statistic::factory()->create();
        $response = $this->json('DELETE', '/v1/statistics/'.$model->id);
        $response->assertStatus(403);
    }

    public function test_non_admin_user_blocked()
    {
        foreach ($this->rolesWithoutAdmins([Role::CONTENT_EDITOR, Role::SUPPORT_STAFF]) as $role) {
            $this->actAs($role);
            $model = Statistic::factory()->create();
            $response = $this->json('DELETE', '/v1/statistics/'.$model->id);
            $response->assertStatus(403);
        }
    }

    public function test_delete_single()
    {
        $this->actAs(Role::CONTENT_EDITOR);

        $model = Statistic::factory()->create();

        $response = $this->json('DELETE', '/v1/statistics/'.$model->id);

        $response->assertStatus(204);
        $this->assertEquals(0, Statistic::count());
    }

    public function test_delete_single_invalid_id_fails()
    {
        $this->actAs(Role::CONTENT_EDITOR);

        $response = $this->json('DELETE', '/v1/statistics/a')
            ->assertExactJson([
                'message' => 'This path was not found.',
            ]);
        $response->assertStatus(404);
    }

    public function test_delete_single_not_found_fails()
    {
        $this->actAs(Role::CONTENT_EDITOR);

        $response = $this->json('DELETE', '/v1/statistics/1')
            ->assertExactJson([
                'message' => 'This item was not found.',
            ]);
        $response->assertStatus(404);
    }
}
