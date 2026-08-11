<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User\CheckOff;
use App\Models\User\TodoBalanceLog;
use Illuminate\Console\Command;

class TodoBackfillMarkDoneLogs extends Command
{
    protected $signature = 'todo:backfill-mark-done-logs {--dry-run : Show what would be created without saving}';

    protected $description = 'Create mark_done balance log entries for each check_off record that lacks one';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $checkOffs = CheckOff::orderBy('occurred_on')->orderBy('id')->get();
        $created = 0;

        foreach ($checkOffs as $co) {
            // Check if a mark_done log already exists for this check_off
            $exists = TodoBalanceLog::where('todo_balance_id', $co->todo_balance_id)
                ->where('reason', TodoBalanceLog::REASON_MARK_DONE)
                ->where('source_type', 'check_off')
                ->where('source_id', $co->id)
                ->exists();

            if ($exists) continue;

            $label = $co->balance->item_key ?? '?';
            $this->line("{$co->occurred_on->format('Y-m-d')}: {$label} G{$co->group_number}"
                . ($co->item_label ? " — {$co->item_label}" : '')
                . " (delta: -1)");

            if (!$dryRun) {
                TodoBalanceLog::create([
                    'user_id' => $co->user_id,
                    'todo_balance_id' => $co->todo_balance_id,
                    'reason' => TodoBalanceLog::REASON_MARK_DONE,
                    'delta' => -1,
                    'balance_before' => 0, // recalc will fix
                    'balance_after' => 0,
                    'occurred_on' => $co->occurred_on->toDateString(),
                    'source_type' => 'check_off',
                    'source_id' => $co->id,
                ]);
            }
            $created++;
        }

        $prefix = $dryRun ? '[DRY RUN] ' : '';
        $this->info("{$prefix}Created {$created} mark_done balance log entries.");

        if (!$dryRun && $created > 0) {
            $this->call('todo:recalc-balance-logs');
        }

        return self::SUCCESS;
    }
}
