<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\Http\InvitationToken;

use App\Models\Role;
use App\Models\User\InvitationToken;
use App\Models\User\User;
use Polis\Tests\Application\ApplicationTestCase;
use Polis\Tests\Traits\MocksApplicationLog;

/**
 * Class InvitationTokenCreateTest
 */
final class InvitationTokenCreateTest extends ApplicationTestCase
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
        $response = $this->json('POST', $this->path);

        $response->assertStatus(403);
    }

    public function test_non_super_admin_user_blocked(): void
    {
        $this->actAsUser();

        $response = $this->json('POST', $this->path);

        $response->assertStatus(403);
    }

    public function test_create_successful_with_no_role(): void
    {
        $this->actAs(Role::SUPER_ADMIN);

        $response = $this->json('POST', $this->path, []);

        $response->assertStatus(201);

        /** @var InvitationToken $invitationToken */
        $invitationToken = InvitationToken::first();
        $this->assertNotNull($invitationToken->token);
        $this->assertEquals(40, strlen($invitationToken->token));
        $this->assertNull($invitationToken->role_id);
        $this->assertNull($invitationToken->used_at);
    }

    public function test_create_successful_with_role(): void
    {
        $this->actAs(Role::SUPER_ADMIN);

        $role = Role::factory()->create();

        $response = $this->json('POST', $this->path, [
            'role_id' => $role->id,
        ]);

        $response->assertStatus(201);

        /** @var InvitationToken $invitationToken */
        $invitationToken = InvitationToken::first();
        $this->assertNotNull($invitationToken->token);
        $this->assertEquals($role->id, $invitationToken->role_id);
    }

    public function test_create_fails_invalid_role_id(): void
    {
        $this->actAs(Role::SUPER_ADMIN);

        $response = $this->json('POST', $this->path, [
            'role_id' => 'not-an-integer',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'errors' => [
                'role_id' => ['The role id must be an integer.'],
            ],
        ]);
    }

    public function test_create_fails_non_existent_role(): void
    {
        $this->actAs(Role::SUPER_ADMIN);

        $response = $this->json('POST', $this->path, [
            'role_id' => 99999,
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'errors' => [
                'role_id' => ['The selected role id is invalid.'],
            ],
        ]);
    }
}
