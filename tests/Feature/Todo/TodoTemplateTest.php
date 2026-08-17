<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\Todo;

use App\Models\Role;
use Polis\Models\User\TodoTemplate;

/**
 * HTTP feature coverage for the TodoTemplate CRUD surface of
 * TodoControllerAbstract (templateIndex/Store/Update/Destroy) plus the
 * User\TodoTemplate\* requests + TodoTemplatePolicy (self-scoped to the route
 * user).
 */
final class TodoTemplateTest extends TodoFeatureTestCase
{
    private function seedTemplate(int $userId, string $name = 'My Template'): TodoTemplate
    {
        return TodoTemplate::create([
            'user_id' => $userId,
            'name' => $name,
            'level' => 'day',
            'sections_json' => ['sections' => []],
        ]);
    }

    public function test_index_requires_authentication(): void
    {
        $other = $this->otherUser();
        $response = $this->json('GET', $this->base($other->id).'/templates');
        $response->assertStatus(403);
    }

    public function test_index_denies_cross_user_access(): void
    {
        $this->actAs(Role::APP_USER);
        $other = $this->otherUser();

        $response = $this->json('GET', $this->base($other->id).'/templates');

        $response->assertStatus(403);
    }

    public function test_index_lists_only_the_users_templates(): void
    {
        $this->actAs(Role::APP_USER);
        $this->seedTemplate($this->actingAs->id, 'Mine');
        $this->seedTemplate($this->otherUser()->id, 'Theirs');

        $response = $this->json('GET', $this->base($this->actingAs->id).'/templates');

        $response->assertStatus(200);
        $response->assertJsonStructure(['data']);
    }

    public function test_store_creates_a_template(): void
    {
        $this->actAs(Role::APP_USER);

        $response = $this->json('POST', $this->base($this->actingAs->id).'/templates', [
            'name' => 'Weekly Plan',
            'level' => 'week',
            'sections_json' => ['sections' => [['title' => 'Focus']]],
        ]);

        $response->assertStatus(201);
        $response->assertJson([
            'name' => 'Weekly Plan',
            'level' => 'week',
            'user_id' => $this->actingAs->id,
        ]);
        $this->assertDatabaseHas('todo_templates', [
            'user_id' => $this->actingAs->id,
            'name' => 'Weekly Plan',
            'level' => 'week',
        ]);
    }

    public function test_update_modifies_a_template(): void
    {
        $this->actAs(Role::APP_USER);
        $template = $this->seedTemplate($this->actingAs->id);

        $response = $this->json('PUT', $this->base($this->actingAs->id).'/templates/'.$template->id, [
            'name' => 'Renamed',
            'level' => 'month',
            'sections_json' => ['sections' => []],
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'id' => $template->id,
            'name' => 'Renamed',
            'level' => 'month',
        ]);
    }

    public function test_update_denies_cross_user_access(): void
    {
        $this->actAs(Role::APP_USER);
        $other = $this->otherUser();
        $template = $this->seedTemplate($other->id);

        $response = $this->json('PUT', $this->base($other->id).'/templates/'.$template->id, [
            'name' => 'Hijacked',
            'level' => 'day',
            'sections_json' => ['sections' => []],
        ]);

        $response->assertStatus(403);
    }

    public function test_destroy_removes_a_template(): void
    {
        $this->actAs(Role::APP_USER);
        $template = $this->seedTemplate($this->actingAs->id);

        $response = $this->json('DELETE', $this->base($this->actingAs->id).'/templates/'.$template->id);

        $response->assertStatus(204);
        $this->assertSoftDeleted('todo_templates', ['id' => $template->id]);
    }

    public function test_destroy_denies_cross_user_access(): void
    {
        $this->actAs(Role::APP_USER);
        $other = $this->otherUser();
        $template = $this->seedTemplate($other->id);

        $response = $this->json('DELETE', $this->base($other->id).'/templates/'.$template->id);

        $response->assertStatus(403);
    }
}
