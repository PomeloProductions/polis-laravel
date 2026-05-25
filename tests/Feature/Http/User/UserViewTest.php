<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\Http\User;

use App\Models\User\User;
use Polis\Tests\DatabaseSetupTrait;
use Polis\Tests\TestCase;
use Polis\Tests\Traits\MocksApplicationLog;

/**
 * Class UserViewTest
 */
final class UserViewTest extends TestCase
{
    use DatabaseSetupTrait, MocksApplicationLog;

    /**
     * @var string
     */
    private $path = '/v1/users/';

    /**
     * @var User
     */
    private $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();
        $this->mockApplicationLog();

        $this->user = User::factory()->create();
        $this->path .= $this->user->id;
    }

    public function test_not_logged_in_user_blocked(): void
    {
        $response = $this->json('GET', $this->path);

        $response->assertStatus(403);
    }

    public function test_not_found(): void
    {
        $this->actAsUser();

        $response = $this->json('GET', '/v1/users/1435');

        $response->assertStatus(404);
    }

    public function test_view_successful(): void
    {
        $this->actAsUser();

        $response = $this->json('GET', $this->path);

        $response->assertStatus(200);

        $data = $this->user->toArray();
        unset($data['resource']);
        $response->assertJson($data);
    }
}
