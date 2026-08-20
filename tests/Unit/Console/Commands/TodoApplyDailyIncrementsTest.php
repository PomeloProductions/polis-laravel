<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Console\Commands;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Polis\Console\Commands\TodoApplyDailyIncrements;
use Polis\Models\User\TodoBalance;
use Polis\Models\User\TodoBalanceLog;
use Polis\Models\User\TodoCalendar;
use Polis\Models\User\TodoSetting;
use Polis\Models\User\TodoTaskNode;
use Polis\Models\User\TodoVacationPeriod;
use Polis\Tests\TestCase;
use Polis\Tests\Traits\CreatesTodoModuleSchema;

/**
 * todo:apply-daily-increments — the vacation-aware daily increment engine.
 *
 * Behavioral contract under test:
 *  - increments ADD tally_step to the balance and are logged with a coherent
 *    balance_before/balance_after chain (reason=daily_increment);
 *  - the FIRST-ever increment for a balance starts TODAY (no backfill to the
 *    seed date), where "today" is the user's timezone-local calendar day;
 *  - subsequent runs catch up one increment per eligible day from the day
 *    AFTER the last daily_increment log (missed cron runs are healed);
 *  - a bare day-of-week `schedule` array on the balance gates eligible days;
 *  - when a node bound to the balance has calendars attached, calendar
 *    resolution replaces the bare schedule, and VACATION days are skipped
 *    unless an "add" calendar covering the date is flagged active_on_vacation;
 *  - schedule-only balances (no calendar node) ignore vacation entirely —
 *    vacation suppression is a per-calendar option;
 *  - balances with tally_step <= 0 are never touched.
 *
 * Frozen clock: 2026-07-15 12:00:00 UTC (a Wednesday, dayOfWeek=3).
 */
final class TodoApplyDailyIncrementsTest extends TestCase
{
    use CreatesTodoModuleSchema;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createTodoModuleTables();
        Carbon::setTestNow(Carbon::parse('2026-07-15 12:00:00', 'UTC'));
        Artisan::registerCommand(new TodoApplyDailyIncrements);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        $this->dropTodoModuleTables();
        parent::tearDown();
    }

    private function makeSettings(int $userId, string $timezone = 'UTC'): TodoSetting
    {
        return TodoSetting::create(['user_id' => $userId, 'timezone' => $timezone]);
    }

    private function makeBalance(int $userId, array $attrs = []): TodoBalance
    {
        return TodoBalance::create(array_merge([
            'user_id' => $userId,
            'item_key' => 'item-'.uniqid(),
            'tracking_mode' => 'hours',
            'balance' => 0,
            'tally_step' => 1,
        ], $attrs));
    }

    private function logIncrement(TodoBalance $balance, string $occurredOn): TodoBalanceLog
    {
        return TodoBalanceLog::create([
            'user_id' => $balance->user_id,
            'todo_balance_id' => $balance->id,
            'reason' => TodoBalanceLog::REASON_DAILY_INCREMENT,
            'delta' => $balance->tally_step,
            'balance_before' => (float) $balance->balance - (float) $balance->tally_step,
            'balance_after' => (float) $balance->balance,
            'occurred_on' => $occurredOn,
        ]);
    }

    /**
     * A node bound to the balance with one "add" calendar attached, so the
     * command resolves scheduling through CalendarResolver instead of the
     * bare schedule array.
     */
    private function bindCalendarNode(TodoBalance $balance, array $calendarAttrs = []): TodoCalendar
    {
        $calendar = TodoCalendar::create(array_merge([
            'user_id' => $balance->user_id,
            'name' => 'Cal '.uniqid(),
            'days_of_week' => [0, 1, 2, 3, 4, 5, 6],
            'active_on_vacation' => true,
        ], $calendarAttrs));

        $node = TodoTaskNode::create([
            'user_page_component_id' => 1,
            'client_id' => 'node-'.uniqid(),
            'task_type' => TodoTaskNode::TASK_TYPE_LINE_ITEM,
            'label' => 'Calendar node',
            'todo_balance_id' => $balance->id,
        ]);
        $node->calendars()->attach($calendar->id, ['mode' => 'add', 'sort_order' => 0]);

        return $calendar;
    }

    /** @return array<int, string> occurred_on dates (Y-m-d) of daily_increment logs, ascending */
    private function incrementDates(TodoBalance $balance): array
    {
        return TodoBalanceLog::where('todo_balance_id', $balance->id)
            ->where('reason', TodoBalanceLog::REASON_DAILY_INCREMENT)
            ->orderBy('occurred_on')
            ->get()
            ->map(fn (TodoBalanceLog $log) => $log->occurred_on->format('Y-m-d'))
            ->all();
    }

    public function test_first_ever_increment_applies_exactly_one_for_today_and_never_backfills(): void
    {
        $this->makeSettings(1);
        $balance = $this->makeBalance(1, ['balance' => 10, 'tally_step' => 1.5, 'created_at' => '2026-03-28 00:00:00']);

        $this->artisan('todo:apply-daily-increments')->assertExitCode(0);

        $logs = TodoBalanceLog::where('todo_balance_id', $balance->id)->get();
        $this->assertCount(1, $logs, 'first run must apply exactly one increment (today), not backfill from creation');

        $log = $logs->first();
        $this->assertSame(TodoBalanceLog::REASON_DAILY_INCREMENT, $log->reason);
        $this->assertSame('2026-07-15', $log->occurred_on->format('Y-m-d'));
        $this->assertEqualsWithDelta(1.5, (float) $log->delta, 0.0001);
        $this->assertEqualsWithDelta(10.0, (float) $log->balance_before, 0.0001);
        $this->assertEqualsWithDelta(11.5, (float) $log->balance_after, 0.0001);

        $this->assertEqualsWithDelta(11.5, (float) $balance->refresh()->balance, 0.0001);
    }

    public function test_catches_up_one_increment_per_day_since_last_daily_increment_log(): void
    {
        $this->makeSettings(1);
        $balance = $this->makeBalance(1, ['balance' => 3, 'tally_step' => 1]);
        $seedLog = $this->logIncrement($balance, '2026-07-12');

        $this->artisan('todo:apply-daily-increments')->assertExitCode(0);

        // Days after the last increment through today: 13th, 14th, 15th.
        $this->assertSame(['2026-07-12', '2026-07-13', '2026-07-14', '2026-07-15'], $this->incrementDates($balance));
        $this->assertEqualsWithDelta(6.0, (float) $balance->refresh()->balance, 0.0001);

        // The before/after chain continues from the pre-run balance.
        $newLogs = TodoBalanceLog::where('todo_balance_id', $balance->id)
            ->where('id', '>', $seedLog->id)
            ->orderBy('occurred_on')
            ->get();
        $this->assertEqualsWithDelta(3.0, (float) $newLogs[0]->balance_before, 0.0001);
        $this->assertEqualsWithDelta(4.0, (float) $newLogs[0]->balance_after, 0.0001);
        $this->assertEqualsWithDelta(5.0, (float) $newLogs[1]->balance_after, 0.0001);
        $this->assertEqualsWithDelta(6.0, (float) $newLogs[2]->balance_after, 0.0001);
    }

    public function test_rerun_on_the_same_day_is_idempotent(): void
    {
        $this->makeSettings(1);
        $balance = $this->makeBalance(1, ['tally_step' => 2]);

        $this->artisan('todo:apply-daily-increments')->assertExitCode(0);
        $this->artisan('todo:apply-daily-increments')->assertExitCode(0);

        $this->assertSame(['2026-07-15'], $this->incrementDates($balance));
        $this->assertEqualsWithDelta(2.0, (float) $balance->refresh()->balance, 0.0001);
    }

    public function test_bare_day_of_week_schedule_gates_catch_up_days(): void
    {
        $this->makeSettings(1);
        // Mondays and Wednesdays only. Catch-up window 07-09..07-15 contains
        // Mon 07-13 and Wed 07-15.
        $balance = $this->makeBalance(1, ['schedule' => [1, 3]]);
        $this->logIncrement($balance, '2026-07-08');

        $this->artisan('todo:apply-daily-increments')->assertExitCode(0);

        $this->assertSame(['2026-07-08', '2026-07-13', '2026-07-15'], $this->incrementDates($balance));
    }

    public function test_balances_with_zero_tally_step_are_ignored(): void
    {
        $this->makeSettings(1);
        $balance = $this->makeBalance(1, ['tally_step' => 0, 'balance' => 5]);

        $this->artisan('todo:apply-daily-increments')->assertExitCode(0);

        $this->assertSame([], $this->incrementDates($balance));
        $this->assertEqualsWithDelta(5.0, (float) $balance->refresh()->balance, 0.0001);
    }

    public function test_today_is_computed_in_the_users_timezone_not_utc(): void
    {
        // 2026-07-15 12:00 UTC is already 2026-07-16 in UTC+14 …
        $this->makeSettings(1, 'Pacific/Kiritimati');
        $ahead = $this->makeBalance(1);

        // … and 2026-07-15 02:00 UTC is still 2026-07-14 in Los Angeles.
        Carbon::setTestNow(Carbon::parse('2026-07-15 02:00:00', 'UTC'));
        $this->makeSettings(2, 'America/Los_Angeles');
        $behind = $this->makeBalance(2);

        $this->artisan('todo:apply-daily-increments')->assertExitCode(0);

        $this->assertSame(['2026-07-14'], $this->incrementDates($behind));
        // The ahead-of-UTC user's "today" at this instant is 2026-07-15 16:00 +14 → 2026-07-15.
        Carbon::setTestNow(Carbon::parse('2026-07-15 12:00:00', 'UTC'));
        $this->artisan('todo:apply-daily-increments')->assertExitCode(0);
        $this->assertSame(['2026-07-15', '2026-07-16'], $this->incrementDates($ahead));
    }

    public function test_vacation_does_not_suppress_schedule_only_balances(): void
    {
        // Vacation suppression is a per-calendar option; a balance with no
        // calendar-bearing node still accrues on vacation days.
        $this->makeSettings(1);
        $balance = $this->makeBalance(1);
        TodoVacationPeriod::create(['user_id' => 1, 'start_date' => '2026-07-10', 'end_date' => null]);

        $this->artisan('todo:apply-daily-increments')->assertExitCode(0);

        $this->assertSame(['2026-07-15'], $this->incrementDates($balance));
    }

    public function test_vacation_suppresses_calendar_scheduled_days_unless_active_on_vacation(): void
    {
        $this->makeSettings(1);
        TodoVacationPeriod::create(['user_id' => 1, 'start_date' => '2026-07-13', 'end_date' => '2026-07-14']);

        // Calendar NOT active on vacation: 13th/14th suppressed, 15th applied.
        $suppressed = $this->makeBalance(1);
        $this->bindCalendarNode($suppressed, ['active_on_vacation' => false]);
        $this->logIncrement($suppressed, '2026-07-12');

        // Calendar active on vacation: all three catch-up days applied.
        $active = $this->makeBalance(1);
        $this->bindCalendarNode($active, ['active_on_vacation' => true]);
        $this->logIncrement($active, '2026-07-12');

        $this->artisan('todo:apply-daily-increments')->assertExitCode(0);

        $this->assertSame(['2026-07-12', '2026-07-15'], $this->incrementDates($suppressed));
        $this->assertEqualsWithDelta(1.0, (float) $suppressed->refresh()->balance, 0.0001);

        $this->assertSame(['2026-07-12', '2026-07-13', '2026-07-14', '2026-07-15'], $this->incrementDates($active));
        $this->assertEqualsWithDelta(3.0, (float) $active->refresh()->balance, 0.0001);
    }

    public function test_calendar_resolution_replaces_bare_schedule_for_calendar_bound_balances(): void
    {
        $this->makeSettings(1);
        // The balance's own schedule says "every day", but the attached
        // calendar only schedules Wednesdays — the calendar must win.
        $balance = $this->makeBalance(1, ['schedule' => [0, 1, 2, 3, 4, 5, 6]]);
        $this->bindCalendarNode($balance, ['days_of_week' => [3]]);
        $this->logIncrement($balance, '2026-07-12');

        $this->artisan('todo:apply-daily-increments')->assertExitCode(0);

        // Catch-up window 13th..15th; only Wed the 15th is calendar-scheduled.
        $this->assertSame(['2026-07-12', '2026-07-15'], $this->incrementDates($balance));
    }
}
