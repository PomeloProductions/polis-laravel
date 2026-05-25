<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\Http\InvitationToken;

use App\Models\Role;
use App\Models\User\InvitationToken;
use App\Models\User\User;
use Polis\Tests\DatabaseSetupTrait;
use Polis\Tests\TestCase;
use Polis\Tests\Traits\MocksApplicationLog;

/**
 * Class InvitationTokenUpdateTest
 */
final class InvitationTokenUpdateTest extends TestCase
{
    use DatabaseSetupTrait, MocksApplicationLog;

    /**
     * @var string
     */
    private $path = '/v1/invitation-tokens/';

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();
        $this->mockApplicationLog();
        User::unsetEventDispatcher();
    }

    public function test_not_logged_in_user_blocked(): void
    {
        $invitationToken = InvitationToken::factory()->create();

        $response = $this->json('PUT', $this->path.$invitationToken->id);

        $response->assertStatus(403);
    }

    public function test_non_super_admin_user_blocked(): void
    {
        $this->actAsUser();
        $invitationToken = InvitationToken::factory()->create();

        $response = $this->json('PUT', $this->path.$invitationToken->id);

        $response->assertStatus(403);
    }

    public function test_not_found(): void
    {
        $this->actAs(Role::SUPER_ADMIN);

        $response = $this->json('PUT', $this->path.'99999');

        $response->assertStatus(404);
    }

    public function test_update_successful(): void
    {
        $this->actAs(Role::SUPER_ADMIN);

        $invitationToken = InvitationToken::factory()->create();
        $role = Role::factory()->create();

        $response = $this->json('PUT', $this->path.$invitationToken->id, [
            'role_id' => $role->id,
        ]);

        $response->assertStatus(200);

        $invitationToken->refresh();
        $this->assertEquals($role->id, $invitationToken->role_id);
    }

    public function test_update_remove_role(): void
    {
        $this->actAs(Role::SUPER_ADMIN);

        $role = Role::factory()->create();
        $invitationToken = InvitationToken::factory()->create([
            'role_id' => $role->id,
        ]);

        $response = $this->json('PUT', $this->path.$invitationToken->id, [
            'role_id' => null,
        ]);

        $response->assertStatus(200);

        $invitationToken->refresh();
        $this->assertNull($invitationToken->role_id);
    }

    public function test_update_fails_invalid_role_id(): void
    {
        $this->actAs(Role::SUPER_ADMIN);

        $invitationToken = InvitationToken::factory()->create();

        $response = $this->json('PUT', $this->path.$invitationToken->id, [
            'role_id' => 'not-an-integer',
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'errors' => [
                'role_id' => ['The role id must be an integer.'],
            ],
        ]);
    }

    public function test_update_fails_non_existent_role(): void
    {
        $this->actAs(Role::SUPER_ADMIN);

        $invitationToken = InvitationToken::factory()->create();

        $response = $this->json('PUT', $this->path.$invitationToken->id, [
            'role_id' => 99999,
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'errors' => [
                'role_id' => ['The selected role id is invalid.'],
            ],
        ]);
    }
}
