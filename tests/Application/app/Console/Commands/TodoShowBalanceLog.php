<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User\TodoBalance;
use App\Models\User\TodoBalanceLog;
use Illuminate\Console\Command;

class TodoShowBalanceLog extends Command
{
    protected $signature = 'todo:show-balance-log {--item= : Item key to show}';

    protected $description = 'Show balance log for a given item';

    public function handle(): int
    {
        $itemKey = $this->option('item');
        if (!$itemKey) {
            $this->error('--item is required');
            return self::FAILURE;
        }

        $balance = TodoBalance::where('item_key', $itemKey)->first();
        if (!$balance) {
            $this->error("No balance found for item: {$itemKey}");
            return self::FAILURE;
        }

        $this->info("Balance ID: {$balance->id} | Item: {$balance->item_key} | Mode: {$balance->tracking_mode} | Current: {$balance->balance}");
        $this->newLine();

        $logs = TodoBalanceLog::where('todo_balance_id', $balance->id)
            ->orderBy('occurred_on')
            ->orderBy('id')
            ->get();

        $headers = ['ID', 'Date', 'Day', 'Reason', 'Delta', 'Before', 'After'];
        $rows = [];
        foreach ($logs as $log) {
            $rows[] = [
                $log->id,
                $log->occurred_on->format('Y-m-d'),
                $log->occurred_on->format('D'),
                $log->reason,
                $log->delta,
                $log->balance_before,
                $log->balance_after,
            ];
        }

        $this->table($headers, $rows);
        $this->info("Total entries: " . count($rows));

        return self::SUCCESS;
    }
}
