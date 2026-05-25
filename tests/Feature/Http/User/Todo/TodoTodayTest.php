<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\Http\User\Todo;

use App\Models\User\User;
use App\Models\User\UserPage;
use Polis\Tests\DatabaseSetupTrait;
use Polis\Tests\TestCase;
use Polis\Tests\Traits\MocksApplicationLog;

class TodoTodayTest extends TestCase
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

        $response = $this->json('GET', $this->path.$user->id.'/todos/today');
        $response->assertStatus(403);
    }

    public function test_different_user_blocked()
    {
        $user = User::factory()->create();
        $this->actAsUser();

        $response = $this->json('GET', $this->path.$user->id.'/todos/today');
        $response->assertStatus(403);
    }

    public function test_today_successful()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Create root todo page
        UserPage::factory()->create([
            'user_id' => $user->id,
            'slug' => 'todos',
            'name' => 'Todos',
            'route_path' => 'todos',
            'page_type' => 'todo',
            'config_json' => ['todo_level' => 'root', 'week_start_day' => 0],
        ]);

        $response = $this->json('GET', $this->path.$user->id.'/todos/today');
        $response->assertStatus(200);
        $response->assertJsonFragment([
            'todo_level' => 'day',
        ]);
    }

    public function test_today_fails_without_root_page()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->json('GET', $this->path.$user->id.'/todos/today');
        $response->assertStatus(500);
    }
}
