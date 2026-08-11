<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User\TimeEntry;
use App\Models\User\UserPageComponent;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Polis\Models\User\UserPage;

class TodoAuditWorkHours extends Command
{
    protected $signature = 'todo:audit-work-hours
        {--item=Poseidon Research Coding : Item key to audit}
        {--start=2026-04-03 : Start date (trusted balance date)}
        {--balance=-87.10 : Trusted starting balance}
        {--step=6 : Daily increment}
        {--schedule=1,2,3,4,5 : Schedule days (0=Sun, comma-separated, or "all")}';

    protected $description = 'Step through a single item day by day and check for discrepancies';

    public function handle(): int
    {
        $itemKey = $this->option('item');
        $startBalance = (float) $this->option('balance');
        $step = (float) $this->option('step');
        $scheduleOpt = $this->option('schedule');
        $schedule = $scheduleOpt === 'all' ? null : array_map('intval', explode(',', $scheduleOpt));

        $seeds = [
            $itemKey => ['balance' => $startBalance, 'step' => $step, 'schedule' => $schedule],
        ];

        // Get all pages sorted chronologically
        $allComps = UserPageComponent::where('component_type', 'todo_task')
            ->orderBy('id')->get()->groupBy('user_page_id');

        $pages = [];
        foreach ($allComps->keys()->sort() as $pageId) {
            $page = UserPage::find($pageId);
            $created = Carbon::parse($page->created_at);
            $date = $created->hour >= 20 ? $created->copy()->addDay()->toDateString() : $created->toDateString();
            $pages[] = ['page_id' => $pageId, 'date' => $date, 'comps' => $allComps[$pageId]];
        }

        // Get all work-hours time entries
        $entries = TimeEntry::where('user_id', 1)
            ->whereNotNull('stopped_at')
            ->where('duration_seconds', '>', 0)
            ->orderBy('started_at')
            ->get();

        $timersByDate = [];
        foreach ($entries as $e) {
            $label = $e->label;
            // Normalize: "Work Hours — Poseidon Research Coding" -> "Poseidon Research Coding"
            if (str_contains($label, ' — ')) {
                $label = explode(' — ', $label)[1];
            }
            // Also handle trailing " -"
            $label = rtrim($label, ' -');

            if (!str_contains($label, $itemKey) && $label !== $itemKey) continue;

            $date = Carbon::parse($e->started_at)->toDateString();
            $hours = round($e->duration_seconds / 3600, 4);
            $timersByDate[$date][$label][] = [
                'id' => $e->id,
                'hours' => $hours,
                'seconds' => $e->duration_seconds,
                'start' => $e->started_at,
                'stop' => $e->stopped_at,
            ];
        }

        // Walk through day by day
        $computed = [];
        foreach ($seeds as $key => $seed) {
            $computed[$key] = $seed['balance'];
        }

        $startDate = $this->option('start');
        $this->info("=== TRUSTED START: {$startDate} ===");
        foreach ($computed as $key => $val) {
            $this->info("  {$key}: {$val}");
        }

        // Find page dates
        $pageByDate = [];
        foreach ($pages as $p) {
            $pageByDate[$p['date']] = $p;
        }

        $currentDate = Carbon::parse($startDate)->addDay();
        $today = Carbon::today();

        while ($currentDate->lte($today)) {
            $dateStr = $currentDate->toDateString();
            $dow = $currentDate->dayOfWeek;
            $dayName = $currentDate->format('l');

            $this->info("\n=== {$dateStr} ({$dayName}) ===");

            // 1. Daily increments
            foreach ($seeds as $key => $seed) {
                $schedule = $seed['schedule'];
                $isScheduled = $schedule === null || in_array($dow, $schedule);

                if ($isScheduled && $seed['step'] > 0) {
                    $old = $computed[$key];
                    $computed[$key] = round($computed[$key] + $seed['step'], 4);
                    $this->line("  [{$key}] daily_increment +{$seed['step']} ({$old} -> {$computed[$key]})");
                }
            }

            // 2. Timer entries
            $dayTimers = $timersByDate[$dateStr] ?? [];
            foreach ($dayTimers as $label => $sessions) {
                $totalHours = 0;
                foreach ($sessions as $s) {
                    $totalHours += $s['hours'];
                    $this->line("  [{$label}] timer #{$s['id']}: {$s['seconds']}s ({$s['hours']}h) {$s['start']} - {$s['stop']}");
                }
                if (isset($computed[$label])) {
                    $old = $computed[$label];
                    $computed[$label] = round($computed[$label] - $totalHours, 4);
                    $this->line("  [{$label}] timer_total -{$totalHours}h ({$old} -> {$computed[$label]})");
                }
            }

            // 3. Compare against page snapshot
            $page = $pageByDate[$dateStr] ?? null;
            if ($page) {
                $pageTallies = [];
                foreach ($page['comps'] as $comp) {
                    $raw = json_decode($comp->getAttributes()['config_json'] ?? '{}', true);
                    $root = $raw['root'] ?? $raw;
                    // Check root level
                    if (isset($root['tally'])) {
                        $pageTallies[$root['label']] = [
                            'tally' => $root['tally'] ?? null,
                            'logged' => $root['logged_hours'] ?? $root['logged_time'] ?? 0,
                        ];
                    }
                    // Check children
                    foreach ($root['children'] ?? [] as $child) {
                        if (isset($child['tally'])) {
                            $pageTallies[$child['label']] = [
                                'tally' => $child['tally'] ?? null,
                                'logged' => $child['logged_hours'] ?? $child['logged_time'] ?? 0,
                            ];
                        }
                        // Check grandchildren
                        foreach ($child['children'] ?? [] as $gc) {
                            if (isset($gc['tally'])) {
                                $pageTallies[$gc['label']] = [
                                    'tally' => $gc['tally'] ?? null,
                                    'logged' => $gc['logged_hours'] ?? $gc['logged_time'] ?? 0,
                                ];
                            }
                        }
                    }
                }

                foreach ($computed as $key => $expectedBalance) {
                    $actual = $pageTallies[$key] ?? null;
                    if (!$actual) continue;

                    $actualTally = round((float) $actual['tally'], 4);
                    $diff = round($actualTally - $expectedBalance, 4);

                    if (abs($diff) > 0.01) {
                        $this->warn("  [{$key}] DISCREPANCY: expected={$expectedBalance}, actual={$actualTally}, diff={$diff}");
                        $computed[$key] = $actualTally;
                    } else {
                        $this->line("  [{$key}] snapshot OK: {$actualTally} (logged_today={$actual['logged']})");
                    }
                }
            } else {
                $this->line("  (no page snapshot for this date)");
            }

            $currentDate->addDay();
        }

        $this->info("\n=== FINAL COMPUTED ===");
        foreach ($computed as $key => $val) {
            $this->info("  {$key}: {$val}");
        }

        return self::SUCCESS;
    }
}
