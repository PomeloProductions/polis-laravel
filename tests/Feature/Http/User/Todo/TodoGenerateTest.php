<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\Http\User\Todo;

use App\Models\User\User;
use App\Models\User\UserPage;
use Polis\Tests\Application\ApplicationTestCase;
use Polis\Tests\Traits\MocksApplicationLog;

class TodoGenerateTest extends ApplicationTestCase
{
    use MocksApplicationLog;

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

        $response = $this->json('POST', $this->path.$user->id.'/todos/generate', [
            'through_date' => '2026-04-01',
        ]);
        $response->assertStatus(403);
    }

    public function test_generate_successful()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        UserPage::factory()->create([
            'user_id' => $user->id,
            'slug' => 'todos',
            'name' => 'Todos',
            'route_path' => 'todos',
            'page_type' => 'todo',
            'config_json' => ['todo_level' => 'root', 'week_start_day' => 0],
        ]);

        $response = $this->json('POST', $this->path.$user->id.'/todos/generate', [
            'through_date' => now()->toDateString(),
        ]);
        $response->assertStatus(200);
        $response->assertJsonFragment([
            'generated_count' => 1,
        ]);
    }

    public function test_generate_validation_fails_without_date()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->json('POST', $this->path.$user->id.'/todos/generate', []);
        $response->assertStatus(400);
    }
}
