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
 * Class InvitationTokenViewTest
 */
final class InvitationTokenViewTest extends TestCase
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

        $response = $this->json('GET', $this->path.$invitationToken->id);

        $response->assertStatus(403);
    }

    public function test_non_super_admin_user_blocked(): void
    {
        $this->actAsUser();
        $invitationToken = InvitationToken::factory()->create();

        $response = $this->json('GET', $this->path.$invitationToken->id);

        $response->assertStatus(403);
    }

    public function test_not_found(): void
    {
        $this->actAs(Role::SUPER_ADMIN);

        $response = $this->json('GET', $this->path.'99999');

        $response->assertStatus(404);
    }

    public function test_view_successful(): void
    {
        $this->actAs(Role::SUPER_ADMIN);

        $invitationToken = InvitationToken::factory()->create();

        $response = $this->json('GET', $this->path.$invitationToken->id);

        $response->assertStatus(200);
        $response->assertJson([
            'id' => $invitationToken->id,
            'token' => $invitationToken->token,
        ]);
    }

    public function test_view_successful_with_role(): void
    {
        $this->actAs(Role::SUPER_ADMIN);

        $role = Role::factory()->create();
        $invitationToken = InvitationToken::factory()->create([
            'role_id' => $role->id,
        ]);

        $response = $this->json('GET', $this->path.$invitationToken->id);

        $response->assertStatus(200);
        $response->assertJson([
            'id' => $invitationToken->id,
            'token' => $invitationToken->token,
            'role_id' => $role->id,
        ]);
    }
}
