<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\Http\User;

use App\Models\User\User;
use Polis\Tests\Application\ApplicationTestCase;
use Polis\Tests\Traits\MocksApplicationLog;

/**
 * Class UserMeTest
 */
final class UserMeTest extends ApplicationTestCase
{
    use MocksApplicationLog;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();
        $this->mockApplicationLog();
    }

    public function test_not_logged_user_blocked(): void
    {
        $response = $this->json('GET', '/v1/users/me');

        $response->assertStatus(403);
    }

    public function test_get_me_success(): void
    {
        User::unsetEventDispatcher();
        /** @var User $myCurrentUser */
        $myCurrentUser = User::factory()->create();

        $this->actingAs($myCurrentUser);

        $response = $this->json('GET', '/v1/users/me');
        $response->assertSimilarJson($myCurrentUser->toArray());
        $response->assertStatus(200);
    }

    public function test_get_me_fails_with_too_many_expands(): void
    {
        $myCurrentUser = User::factory()->create();

        $this->actingAs($myCurrentUser);

        $response = $this->json('GET', '/v1/users/me?expand[roles.users]=*');

        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Sorry, something went wrong.',
            'details' => 'The relation roles.users cannot be expanded on this request.',
        ]);
    }
}
