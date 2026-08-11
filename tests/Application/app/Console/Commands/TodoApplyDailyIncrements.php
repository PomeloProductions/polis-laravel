<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User\TodoBalance;
use App\Models\User\TodoBalanceLog;
use App\Models\User\TodoSetting;
use App\Models\User\TodoTaskNode;
use App\Models\User\TodoVacationPeriod;
use App\Services\Todo\CalendarResolver;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class TodoApplyDailyIncrements extends Command
{
    protected $signature = 'todo:apply-daily-increments';

    protected $description = 'Apply daily balance increments based on each user\'s timezone';

    public function handle(): int
    {
        $count = 0;

        // Process each user's balances using their timezone
        $balances = TodoBalance::where('tally_step', '>', 0)->get()->groupBy('user_id');

        foreach ($balances as $userId => $userBalances) {
            $settings = TodoSetting::where('user_id', $userId)->first();
            $timezone = $settings->timezone ?? 'UTC';
            $userToday = Carbon::now($timezone)->startOfDay();

            // Preload this user's vacation periods so we can flag vacation days per-date during
            // the catch-up loop without a query per day.
            $vacationPeriods = TodoVacationPeriod::where('user_id', $userId)->get();

            foreach ($userBalances as $balance) {
                $lastIncrement = TodoBalanceLog::where('todo_balance_id', $balance->id)
                    ->where('reason', TodoBalanceLog::REASON_DAILY_INCREMENT)
                    ->orderBy('occurred_on', 'desc')
                    ->first();

                if ($lastIncrement) {
                    // Catch up from the day after the last increment (handles missed cron runs).
                    $startDate = Carbon::parse($lastIncrement->occurred_on->format('Y-m-d'), $timezone)->addDay();
                } else {
                    // First-ever increment for this balance: start TODAY. Never backfill from the
                    // seed date — a balance that only just had its daily budget enabled should not
                    // retroactively accrue months of increments dating back to when it was created.
                    $startDate = $userToday->copy();
                }

                $schedule = $balance->schedule;
                // If any node bound to this balance has calendar_rules, prefer the calendar
                // resolution over the bare day-of-week schedule (so holidays etc. are excluded).
                $calendarNode = TodoTaskNode::where('todo_balance_id', $balance->id)
                    ->whereHas('calendars')
                    ->with('calendars')
                    ->orderByDesc('id')
                    ->first();
                $date = $startDate->copy()->startOfDay();
                $applied = 0;

                while ($date->format('Y-m-d') <= $userToday->format('Y-m-d')) {
                    if ($calendarNode) {
                        $isScheduled = CalendarResolver::resolveCalendars($calendarNode->calendars, $date);

                        // On a vacation day, suppress the increment unless at least one of the
                        // calendars scheduling this date is active on vacation. Schedule-only
                        // nodes (no calendars) are unaffected — vacation is a per-calendar option.
                        if ($isScheduled
                            && TodoVacationPeriod::dateIsVacation($vacationPeriods, $date)
                            && ! CalendarResolver::anyActiveOnVacation($calendarNode->calendars, $date)) {
                            $isScheduled = false;
                        }
                    } else {
                        $isScheduled = $schedule === null || in_array($date->dayOfWeek, $schedule);
                    }

                    if ($isScheduled) {
                        $before = (float) $balance->balance;
                        $after = round($before + (float) $balance->tally_step, 4);

                        TodoBalanceLog::create([
                            'user_id' => $balance->user_id,
                            'todo_balance_id' => $balance->id,
                            'reason' => TodoBalanceLog::REASON_DAILY_INCREMENT,
                            'delta' => $balance->tally_step,
                            'balance_before' => $before,
                            'balance_after' => $after,
                            'occurred_on' => $date->toDateString(),
                        ]);

                        $balance->updateQuietly(['balance' => $after]);
                        $applied++;
                        $count++;
                    }

                    $date->addDay();
                }

                if ($applied > 0) {
                    $this->line("{$balance->item_key}: +{$applied} increments → balance={$balance->balance}");
                }
            }
        }

        $this->info("Applied {$count} daily increment(s).");

        return self::SUCCESS;
    }
}
