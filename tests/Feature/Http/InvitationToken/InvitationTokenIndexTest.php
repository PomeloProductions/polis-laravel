<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\Http\InvitationToken;

use App\Models\Role;
use App\Models\User\InvitationToken;
use App\Models\User\User;
use Polis\Tests\Application\ApplicationTestCase;
use Polis\Tests\Traits\MocksApplicationLog;

/**
 * Class InvitationTokenIndexTest
 */
final class InvitationTokenIndexTest extends ApplicationTestCase
{
    use MocksApplicationLog;

    /**
     * @var string
     */
    private $path = '/v1/invitation-tokens';

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
        $response->assertJson([
            'total' => 0,
            'data' => [],
        ]);
    }

    public function test_get_pagination_result(): void
    {
        $this->actAs(Role::SUPER_ADMIN);

        InvitationToken::factory()->count(15)->create();

        // first page
        $response = $this->json('GET', $this->path);
        $response->assertStatus(200);
        $response->assertJson([
            'total' => 15,
            'current_page' => 1,
            'per_page' => 10,
            'from' => 1,
            'to' => 10,
            'last_page' => 2,
        ])
            ->assertJsonStructure([
                'data' => [
                    '*' => array_keys((new InvitationToken)->toArray()),
                ],
            ]);

        // second page
        $response = $this->json('GET', $this->path.'?page=2');
        $response->assertStatus(200);
        $response->assertJson([
            'total' => 15,
            'current_page' => 2,
            'per_page' => 10,
            'from' => 11,
            'to' => 15,
            'last_page' => 2,
        ])
            ->assertJsonStructure([
                'data' => [
                    '*' => array_keys((new InvitationToken)->toArray()),
                ],
            ]);

        // page with limit
        $response = $this->json('GET', $this->path.'?page=2&limit=5');
        $response->assertStatus(200);
        $response->assertJson([
            'total' => 15,
            'current_page' => 2,
            'per_page' => 5,
            'from' => 6,
            'to' => 10,
            'last_page' => 3,
        ])
            ->assertJsonStructure([
                'data' => [
                    '*' => array_keys((new InvitationToken)->toArray()),
                ],
            ]);
    }

    public function test_get_pagination_with_expands(): void
    {
        $this->actAs(Role::SUPER_ADMIN);

        $role = Role::factory()->create();
        InvitationToken::factory()->count(5)->create([
            'role_id' => $role->id,
        ]);

        // with expands
        $response = $this->json('GET', $this->path.'?expand[role]=*');
        $response->assertStatus(200);
        $response->assertJson([
            'total' => 5,
            'current_page' => 1,
            'per_page' => 10,
            'from' => 1,
            'to' => 5,
            'last_page' => 1,
        ])
            ->assertJsonStructure([
                'data' => [
                    '*' => array_keys((new InvitationToken)->toArray()),
                ],
            ]);
    }
}
