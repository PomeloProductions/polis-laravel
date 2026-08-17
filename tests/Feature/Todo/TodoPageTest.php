<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\Todo;

use App\Models\Role;

/**
 * HTTP feature coverage for the period-page surface of
 * Polis\Http\Core\Controllers\User\TodoControllerAbstract:
 * today / navigate / hierarchy / resolve / generate. These drive the real
 * period-generation pipeline (PeriodPageGenerationService + TodoPeriodLadder)
 * and the balance-aware page serializer.
 *
 * Authorization for the whole surface is self-scoped via TodoSettingPolicy
 * (getPolicyModel() = TodoSetting, keyed on the route `user`): a user may only
 * touch their own todo pages.
 */
final class TodoPageTest extends TodoFeatureTestCase
{
    public function test_today_requires_authentication(): void
    {
        $other = $this->otherUser();
        $response = $this->json('GET', $this->base($other->id).'/today');
        $response->assertStatus(403);
    }

    public function test_today_denies_cross_user_access(): void
    {
        $this->actAs(Role::APP_USER);
        $other = $this->otherUser();
        $this->createRootTodoPage($other->id);

        $response = $this->json('GET', $this->base($other->id).'/today');

        $response->assertStatus(403);
    }

    public function test_today_generates_and_returns_current_day_page(): void
    {
        $this->actAs(Role::APP_USER);
        $this->createRootTodoPage($this->actingAs->id);

        $response = $this->json('GET', $this->base($this->actingAs->id).'/today');

        $response->assertStatus(200);
        // The generated leaf is a day-level page and the serializer attaches
        // the user's balances.
        $response->assertJsonPath('config_json.todo_level', 'day');
        $response->assertJsonStructure(['balances']);
    }

    public function test_navigate_by_level_returns_a_page(): void
    {
        $this->actAs(Role::APP_USER);
        $this->createRootTodoPage($this->actingAs->id);

        $response = $this->json('GET', $this->base($this->actingAs->id).'/navigate?level=day');

        $response->assertStatus(200);
        $response->assertJsonPath('config_json.todo_level', 'day');
    }

    public function test_hierarchy_without_root_page_is_not_found(): void
    {
        // No root todo page exists for this user -> the controller reports 404.
        $this->actAs(Role::APP_USER);

        $response = $this->json('GET', $this->base($this->actingAs->id).'/hierarchy');

        $response->assertStatus(404);
    }

    public function test_hierarchy_with_root_page_returns_tree(): void
    {
        $this->actAs(Role::APP_USER);
        $this->createRootTodoPage($this->actingAs->id);
        // Seed the ladder for the current year so the hierarchy has content.
        $this->json('GET', $this->base($this->actingAs->id).'/today')->assertStatus(200);

        $response = $this->json('GET', $this->base($this->actingAs->id).'/hierarchy');

        $response->assertStatus(200);
        $response->assertJsonStructure(['months']);
    }

    public function test_resolve_unknown_slug_is_not_found(): void
    {
        $this->actAs(Role::APP_USER);
        $this->createRootTodoPage($this->actingAs->id);

        $response = $this->json('GET', $this->base($this->actingAs->id).'/resolve?slug=does-not-exist');

        $response->assertStatus(404);
    }

    public function test_generate_creates_pages_through_date(): void
    {
        $this->actAs(Role::APP_USER);
        $this->createRootTodoPage($this->actingAs->id);

        $response = $this->json('POST', $this->base($this->actingAs->id).'/generate', [
            'through_date' => now()->addDay()->toDateString(),
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['generated_count', 'through_date']);
        $this->assertGreaterThanOrEqual(1, $response->json('generated_count'));
    }
}
