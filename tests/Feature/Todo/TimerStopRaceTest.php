<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\Todo;

use App\Models\User\User;
use Carbon\Carbon;
use Polis\Models\User\TimeEntry;
use Polis\Tests\Application\ApplicationTestCase;
use Polis\Tests\Traits\MocksApplicationLog;

/**
 * DELETE /todos/timer must only stop the entry the client meant. Regression for a live
 * incident (2026-08-10): a stop-then-switch fired DELETE and POST near-simultaneously, the
 * POST was processed first (auto-stopping the old entry and creating the new one), and the
 * untargeted DELETE then stopped the FRESHLY-CREATED entry at ~0 seconds — losing its time
 * silently, because a ~0h delta is below the balance-logging threshold.
 */
final class TimerStopRaceTest extends ApplicationTestCase
{
    use MocksApplicationLog;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();
        $this->mockApplicationLog();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    private function makeEntry(array $attrs = []): TimeEntry
    {
        return TimeEntry::create(array_merge([
            'user_id' => $this->user->id,
            'label' => 'Old Task',
            'item_id' => 'tn-old',
            'started_at' => Carbon::now()->subMinutes(10),
            'stopped_at' => null,
            'duration_seconds' => 0,
        ], $attrs));
    }

    private function stopPath(array $params = []): string
    {
        $path = '/v1/users/'.$this->user->id.'/todos/timer';

        return $params ? $path.'?'.http_build_query($params) : $path;
    }

    /**
     * The core regression: a stop targeted at the OLD entry arrives after that entry was
     * already auto-stopped by the next task's start. The new running entry must survive.
     * (On the old code the DELETE stopped whatever was running — the new entry — at ~0s.)
     */
    public function test_stale_targeted_stop_does_not_touch_newer_running_entry(): void
    {
        $old = $this->makeEntry([
            'stopped_at' => Carbon::now(),
            'duration_seconds' => 600,
        ]);
        $new = $this->makeEntry([
            'label' => 'New Task',
            'item_id' => 'tn-new',
            'started_at' => Carbon::now(),
        ]);

        $response = $this->json('DELETE', $this->stopPath(['entry_id' => $old->id, 'item_id' => 'tn-old']));

        $response->assertStatus(204);
        $this->assertNull($new->fresh()->stopped_at, 'The newer running entry must keep running.');
        $this->assertEquals(600, $old->fresh()->duration_seconds, 'The already-stopped entry must be untouched.');
    }

    public function test_targeted_stop_stops_the_matching_entry(): void
    {
        $entry = $this->makeEntry();

        $response = $this->json('DELETE', $this->stopPath(['entry_id' => $entry->id, 'item_id' => 'tn-old']));

        $response->assertStatus(200);
        $entry->refresh();
        $this->assertNotNull($entry->stopped_at);
        $this->assertGreaterThanOrEqual(590, $entry->duration_seconds);
    }

    public function test_item_targeted_stop_matches_by_item_when_entry_id_unknown(): void
    {
        // entryId is 0 client-side while the start response is in flight — item_id still targets.
        $other = $this->makeEntry(['item_id' => 'tn-other', 'label' => 'Other']);
        $entry = $this->makeEntry();

        $response = $this->json('DELETE', $this->stopPath(['item_id' => 'tn-old']));

        $response->assertStatus(200);
        $this->assertNotNull($entry->fresh()->stopped_at);
        $this->assertNull($other->fresh()->stopped_at, 'Only the targeted item may be stopped.');
    }

    public function test_untargeted_stop_still_stops_the_running_entry(): void
    {
        $entry = $this->makeEntry();

        $response = $this->json('DELETE', $this->stopPath());

        $response->assertStatus(200);
        $this->assertNotNull($entry->fresh()->stopped_at);
    }

    public function test_stop_with_no_running_entry_is_a_noop(): void
    {
        $this->makeEntry([
            'stopped_at' => Carbon::now(),
            'duration_seconds' => 600,
        ]);

        $response = $this->json('DELETE', $this->stopPath());

        $response->assertStatus(204);
    }
}
