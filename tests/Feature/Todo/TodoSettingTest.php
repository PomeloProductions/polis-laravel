<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\Todo;

use App\Models\Role;

/**
 * HTTP feature coverage for TodoControllerAbstract@settings / updateSettings.
 * settings() lazily creates a default TodoSetting row on first read.
 */
final class TodoSettingTest extends TodoFeatureTestCase
{
    public function test_settings_requires_authentication(): void
    {
        $other = $this->otherUser();
        $response = $this->json('GET', $this->base($other->id).'/settings');
        $response->assertStatus(403);
    }

    public function test_settings_denies_cross_user_access(): void
    {
        $this->actAs(Role::APP_USER);
        $other = $this->otherUser();

        $response = $this->json('GET', $this->base($other->id).'/settings');

        $response->assertStatus(403);
    }

    public function test_settings_lazily_creates_and_returns_defaults(): void
    {
        $this->actAs(Role::APP_USER);

        $response = $this->json('GET', $this->base($this->actingAs->id).'/settings');

        $response->assertStatus(200);
        $response->assertJson([
            'user_id' => $this->actingAs->id,
            'week_start_day' => 0,
        ]);
    }

    public function test_update_settings_persists_changes(): void
    {
        $this->actAs(Role::APP_USER);

        $response = $this->json('PUT', $this->base($this->actingAs->id).'/settings', [
            'week_start_day' => 1,
            'timezone' => 'UTC',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'user_id' => $this->actingAs->id,
            'week_start_day' => 1,
            'timezone' => 'UTC',
        ]);
    }

    public function test_update_settings_denies_cross_user_access(): void
    {
        $this->actAs(Role::APP_USER);
        $other = $this->otherUser();

        $response = $this->json('PUT', $this->base($other->id).'/settings', [
            'week_start_day' => 1,
            'timezone' => 'UTC',
        ]);

        $response->assertStatus(403);
    }
}
