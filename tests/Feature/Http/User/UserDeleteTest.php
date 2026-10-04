<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\Http\User;

use App\Models\Role;
use App\Models\User\User;
use Polis\Tests\Application\ApplicationTestCase;
use Polis\Tests\Traits\MocksApplicationLog;

/**
 * Class UserDeleteTest
 *
 * Covers DELETE /v1/users/{user} (UserControllerAbstract::destroy). Deleting a
 * user is gated by UserPolicy::delete() which returns false for everyone; only
 * the SUPER_ADMIN before() bypass grants access. Users are soft deleted (the
 * User model extends BaseModelAbstract which uses SoftDeletes).
 */
final class UserDeleteTest extends ApplicationTestCase
{
    use MocksApplicationLog;

    /**
     * @var string
     */
    private $path = '/v1/users/';

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();
        $this->mockApplicationLog();
        User::unsetEventDispatcher();
    }

    public function test_not_logged_in_user_blocked(): void
    {
        $user = User::factory()->create();

        $response = $this->json('DELETE', $this->path.$user->id);

        $response->assertStatus(403);
    }

    public function test_non_super_admin_user_blocked(): void
    {
        $this->actAsUser();
        $user = User::factory()->create();

        $response = $this->json('DELETE', $this->path.$user->id);

        $response->assertStatus(403);
    }

    public function test_user_cannot_delete_self_without_super_admin(): void
    {
        $this->actAsUser();

        $response = $this->json('DELETE', $this->path.$this->actingAs->id);

        $response->assertStatus(403);
    }

    public function test_not_found(): void
    {
        $this->actAs(Role::SUPER_ADMIN);
        $missingId = ((int) User::max('id')) + 1000;

        $response = $this->json('DELETE', $this->path.$missingId);

        $response->assertStatus(404);
    }

    public function test_delete_successful(): void
    {
        $this->actAs(Role::SUPER_ADMIN);

        $user = User::factory()->create();
        $userId = $user->id;

        $response = $this->json('DELETE', $this->path.$userId);

        $response->assertStatus(204);

        $this->assertSoftDeleted('users', ['id' => $userId]);
    }
}
