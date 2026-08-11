<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User\TodoBalance;
use App\Models\User\TodoBalanceLog;
use Illuminate\Console\Command;

class TodoRecalcBalanceLogs extends Command
{
    protected $signature = 'todo:recalc-balance-logs {--balance-id= : Specific balance ID, or all if omitted}';

    protected $description = 'Recalculate before/after chain for balance log entries and update current balance';

    public function handle(): int
    {
        $balanceId = $this->option('balance-id');

        $query = TodoBalance::query();
        if ($balanceId) {
            $query->where('id', $balanceId);
        }

        $balances = $query->get();

        foreach ($balances as $bal) {
            $logs = TodoBalanceLog::where('todo_balance_id', $bal->id)
                ->orderBy('occurred_on')
                ->orderBy('id')
                ->get();

            $running = 0.0;
            foreach ($logs as $log) {
                $before = $running;
                $running = round($running + (float) $log->delta, 2);

                if ((float) $log->balance_before !== $before || (float) $log->balance_after !== $running) {
                    $log->balance_before = $before;
                    $log->balance_after = $running;
                    $log->save();
                }
            }

            $bal->balance = $running;
            $bal->save();

            $this->line("{$bal->item_key}: {$logs->count()} entries, final balance={$running}");
        }

        return self::SUCCESS;
    }
}
