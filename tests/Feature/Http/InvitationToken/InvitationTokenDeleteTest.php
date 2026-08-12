<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\Http\InvitationToken;

use App\Models\Role;
use App\Models\User\InvitationToken;
use App\Models\User\User;
use Polis\Tests\Application\ApplicationTestCase;
use Polis\Tests\Traits\MocksApplicationLog;

/**
 * Class InvitationTokenDeleteTest
 */
final class InvitationTokenDeleteTest extends ApplicationTestCase
{
    use MocksApplicationLog;

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

        $response = $this->json('DELETE', $this->path.$invitationToken->id);

        $response->assertStatus(403);
    }

    public function test_non_super_admin_user_blocked(): void
    {
        $this->actAsUser();
        $invitationToken = InvitationToken::factory()->create();

        $response = $this->json('DELETE', $this->path.$invitationToken->id);

        $response->assertStatus(403);
    }

    public function test_not_found(): void
    {
        $this->actAs(Role::SUPER_ADMIN);

        $response = $this->json('DELETE', $this->path.'99999');

        $response->assertStatus(404);
    }

    public function test_delete_successful(): void
    {
        $this->actAs(Role::SUPER_ADMIN);

        $invitationToken = InvitationToken::factory()->create();
        $tokenId = $invitationToken->id;

        $response = $this->json('DELETE', $this->path.$tokenId);

        $response->assertStatus(204);

        // Verify the token is soft deleted
        $this->assertSoftDeleted('invitation_tokens', ['id' => $tokenId]);
    }
}
