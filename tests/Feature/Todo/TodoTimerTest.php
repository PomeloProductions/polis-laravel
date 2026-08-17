<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\Todo;

use App\Models\Role;
use Polis\Models\User\TimeEntry;

/**
 * HTTP feature coverage for the timer + time-entry surface of
 * TodoControllerAbstract (timerShow/Start/Stop + timeEntryIndex/Store/
 * Update/Destroy). These paths open + close TimerSessions and append the
 * TodoBalanceLog ledger, so they exercise a large slice of the controller.
 */
final class TodoTimerTest extends TodoFeatureTestCase
{
    public function test_timer_show_requires_authentication(): void
    {
        $other = $this->otherUser();
        $response = $this->json('GET', $this->base($other->id).'/timer');
        $response->assertStatus(403);
    }

    public function test_timer_show_denies_cross_user_access(): void
    {
        $this->actAs(Role::APP_USER);
        $other = $this->otherUser();
        $response = $this->json('GET', $this->base($other->id).'/timer');
        $response->assertStatus(403);
    }

    public function test_timer_show_returns_null_when_no_running_timer(): void
    {
        $this->actAs(Role::APP_USER);
        $response = $this->json('GET', $this->base($this->actingAs->id).'/timer');
        $response->assertStatus(200);
        // No running timer -> the controller returns an empty/no-timer body
        // (no `entry`/`session` payload).
        $this->assertArrayNotHasKey('entry', (array) $response->json());
    }

    public function test_timer_start_opens_a_running_entry_and_session(): void
    {
        $this->actAs(Role::APP_USER);

        $response = $this->json('POST', $this->base($this->actingAs->id).'/timer', [
            'label' => 'Deep work',
            'started_at' => now()->toIso8601String(),
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure(['entry', 'session']);
        $this->assertDatabaseHas('time_entries', [
            'user_id' => $this->actingAs->id,
            'label' => 'Deep work',
            'stopped_at' => null,
        ]);
    }

    public function test_timer_stop_closes_the_running_entry(): void
    {
        $this->actAs(Role::APP_USER);
        $this->json('POST', $this->base($this->actingAs->id).'/timer', [
            'label' => 'Deep work',
            'started_at' => now()->subMinutes(30)->toIso8601String(),
        ])->assertStatus(201);

        $response = $this->json('DELETE', $this->base($this->actingAs->id).'/timer');

        $response->assertStatus(200);
        $response->assertJsonStructure(['entry', 'session']);
        $this->assertNull(
            TimeEntry::where('user_id', $this->actingAs->id)->whereNull('stopped_at')->first(),
        );
    }

    public function test_time_entry_index_lists_completed_entries(): void
    {
        $this->actAs(Role::APP_USER);
        TimeEntry::create([
            'user_id' => $this->actingAs->id,
            'label' => 'done',
            'started_at' => now()->subHours(2),
            'stopped_at' => now()->subHour(),
            'duration_seconds' => 3600,
        ]);

        $response = $this->json('GET', $this->base($this->actingAs->id).'/time-entries');

        $response->assertStatus(200);
        $response->assertJsonStructure(['data']);
        $this->assertCount(1, $response->json('data'));
    }

    public function test_time_entry_store_creates_a_completed_entry(): void
    {
        $this->actAs(Role::APP_USER);

        $response = $this->json('POST', $this->base($this->actingAs->id).'/time-entries', [
            'label' => 'logged',
            'started_at' => now()->subHour()->toIso8601String(),
            'stopped_at' => now()->toIso8601String(),
            'duration_seconds' => 3600,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('time_entries', [
            'user_id' => $this->actingAs->id,
            'label' => 'logged',
        ]);
    }

    public function test_time_entry_update_rejects_entries_owned_by_others(): void
    {
        // The controller itself guards cross-user access on the entry with a
        // 403 (the entry's user_id must match the route user).
        $this->actAs(Role::APP_USER);
        $foreignEntry = TimeEntry::create([
            'user_id' => $this->otherUser()->id,
            'label' => 'theirs',
            'started_at' => now()->subHour(),
            'stopped_at' => now(),
            'duration_seconds' => 3600,
        ]);

        $response = $this->json('PUT', $this->base($this->actingAs->id).'/time-entries/'.$foreignEntry->id, [
            'label' => 'hijack',
            'started_at' => now()->subHour()->toIso8601String(),
            'stopped_at' => now()->toIso8601String(),
            'duration_seconds' => 3600,
        ]);

        $response->assertStatus(403);
    }

    public function test_time_entry_destroy_removes_the_entry(): void
    {
        $this->actAs(Role::APP_USER);
        $entry = TimeEntry::create([
            'user_id' => $this->actingAs->id,
            'label' => 'remove me',
            'started_at' => now()->subHour(),
            'stopped_at' => now(),
            'duration_seconds' => 3600,
        ]);

        $response = $this->json('DELETE', $this->base($this->actingAs->id).'/time-entries/'.$entry->id);

        $response->assertStatus(204);
        $this->assertSoftDeleted('time_entries', ['id' => $entry->id]);
    }
}
