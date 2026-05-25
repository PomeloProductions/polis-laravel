<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\Http\User\Todo;

use App\Models\User\TodoTemplate;
use App\Models\User\User;
use Polis\Tests\DatabaseSetupTrait;
use Polis\Tests\TestCase;
use Polis\Tests\Traits\MocksApplicationLog;

class TodoTemplateTest extends TestCase
{
    use DatabaseSetupTrait, MocksApplicationLog;

    private string $path = '/v1/users/';

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();
        $this->mockApplicationLog();
    }

    public function test_index_not_logged_in_blocked()
    {
        $user = User::factory()->create();

        $response = $this->json('GET', $this->path.$user->id.'/todos/templates');
        $response->assertStatus(403);
    }

    public function test_index_successful()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        TodoTemplate::factory()->create([
            'user_id' => $user->id,
            'name' => 'Day Template',
            'level' => 'day',
        ]);

        $response = $this->json('GET', $this->path.$user->id.'/todos/templates');
        $response->assertStatus(200);
        $response->assertJsonFragment([
            'name' => 'Day Template',
        ]);
    }

    public function test_store_successful()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->json('POST', $this->path.$user->id.'/todos/templates', [
            'name' => 'My Day Template',
            'level' => 'day',
            'sections_json' => [
                ['key' => 's1', 'label' => 'Section 1', 'type' => 'todo_bullet_list', 'config' => []],
            ],
        ]);

        $response->assertStatus(201);
        $response->assertJsonFragment([
            'name' => 'My Day Template',
            'level' => 'day',
        ]);

        $this->assertDatabaseHas('todo_templates', [
            'user_id' => $user->id,
            'name' => 'My Day Template',
        ]);
    }

    public function test_store_validation_fails()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->json('POST', $this->path.$user->id.'/todos/templates', [
            'name' => 'Missing Fields',
        ]);

        $response->assertStatus(400);
    }

    public function test_store_invalid_level()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->json('POST', $this->path.$user->id.'/todos/templates', [
            'name' => 'Test',
            'level' => 'invalid',
            'sections_json' => [],
        ]);

        $response->assertStatus(400);
    }

    public function test_update_successful()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $template = TodoTemplate::factory()->create([
            'user_id' => $user->id,
            'name' => 'Original',
            'level' => 'day',
        ]);

        $response = $this->json('PUT', $this->path.$user->id.'/todos/templates/'.$template->id, [
            'name' => 'Updated',
        ]);

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'name' => 'Updated',
        ]);
    }

    public function test_destroy_successful()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $template = TodoTemplate::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->json('DELETE', $this->path.$user->id.'/todos/templates/'.$template->id);
        $response->assertStatus(204);
    }

    public function test_destroy_different_user_blocked()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $this->actingAs($otherUser);

        $template = TodoTemplate::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->json('DELETE', $this->path.$user->id.'/todos/templates/'.$template->id);
        $response->assertStatus(403);
    }
}
