<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\Http\User;

use App\Models\Role;
use App\Models\User\User;
use Polis\Tests\Application\ApplicationTestCase;
use Polis\Tests\Traits\MocksApplicationLog;

/**
 * Class UserIndexTest
 *
 * Covers GET /v1/users (UserControllerAbstract::index). Listing all users is
 * gated by UserPolicy::all() which returns false for everyone; only the
 * SUPER_ADMIN before() bypass grants access.
 */
final class UserIndexTest extends ApplicationTestCase
{
    use MocksApplicationLog;

    /**
     * @var string
     */
    private $path = '/v1/users';

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();
        $this->mockApplicationLog();
        User::unsetEventDispatcher();
    }

    public function test_not_logged_in_user_blocked(): void
    {
        $response = $this->json('GET', $this->path);

        $response->assertStatus(403);
    }

    public function test_non_super_admin_user_blocked(): void
    {
        $this->actAsUser();

        $response = $this->json('GET', $this->path);

        $response->assertStatus(403);
    }

    public function test_super_admin_can_access(): void
    {
        $this->actAs(Role::SUPER_ADMIN);

        $response = $this->json('GET', $this->path);

        $response->assertStatus(200);
        // Only the acting super admin exists.
        $response->assertJson([
            'total' => 1,
            'current_page' => 1,
        ]);
    }

    public function test_get_pagination_result(): void
    {
        $this->actAs(Role::SUPER_ADMIN);

        // 15 additional users plus the acting super admin = 16 total.
        User::factory()->count(15)->create();

        // first page
        $response = $this->json('GET', $this->path);
        $response->assertStatus(200);
        $response->assertJson([
            'total' => 16,
            'current_page' => 1,
            'per_page' => 10,
            'from' => 1,
            'to' => 10,
            'last_page' => 2,
        ])
            ->assertJsonStructure([
                'data' => [
                    '*' => array_keys((new User)->toArray()),
                ],
            ]);

        // second page
        $response = $this->json('GET', $this->path.'?page=2');
        $response->assertStatus(200);
        $response->assertJson([
            'total' => 16,
            'current_page' => 2,
            'per_page' => 10,
            'from' => 11,
            'to' => 16,
            'last_page' => 2,
        ]);

        // page with limit
        $response = $this->json('GET', $this->path.'?page=2&limit=5');
        $response->assertStatus(200);
        $response->assertJson([
            'total' => 16,
            'current_page' => 2,
            'per_page' => 5,
            'from' => 6,
            'to' => 10,
            'last_page' => 4,
        ]);
    }

    public function test_get_pagination_with_expands(): void
    {
        $this->actAs(Role::SUPER_ADMIN);

        User::factory()->count(5)->create();

        $response = $this->json('GET', $this->path.'?expand[roles]=*');
        $response->assertStatus(200);
        $response->assertJson([
            'total' => 6,
            'current_page' => 1,
            'per_page' => 10,
            'from' => 1,
            'to' => 6,
            'last_page' => 1,
        ]);
    }
}
