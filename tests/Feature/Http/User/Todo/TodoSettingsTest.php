<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\Http\User\Todo;

use App\Models\User\User;
use Polis\Tests\DatabaseSetupTrait;
use Polis\Tests\TestCase;
use Polis\Tests\Traits\MocksApplicationLog;

class TodoSettingsTest extends TestCase
{
    use DatabaseSetupTrait, MocksApplicationLog;

    private string $path = '/v1/users/';

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();
        $this->mockApplicationLog();
    }

    public function test_not_logged_in_user_blocked()
    {
        $user = User::factory()->create();

        $response = $this->json('GET', $this->path.$user->id.'/todos/settings');
        $response->assertStatus(403);
    }

    public function test_get_settings_creates_default()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->json('GET', $this->path.$user->id.'/todos/settings');
        $response->assertStatus(200);
        $response->assertJsonFragment([
            'week_start_day' => 0,
        ]);
    }

    public function test_update_settings()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // First create default
        $this->json('GET', $this->path.$user->id.'/todos/settings');

        $response = $this->json('PUT', $this->path.$user->id.'/todos/settings', [
            'week_start_day' => 1,
        ]);
        $response->assertStatus(200);
        $response->assertJsonFragment([
            'week_start_day' => 1,
        ]);

        $this->assertDatabaseHas('todo_settings', [
            'user_id' => $user->id,
            'week_start_day' => 1,
        ]);
    }
}
