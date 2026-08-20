<?php

declare(strict_types=1);

namespace Polis\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Polis\Models\User\TodoBalance;
use Polis\Models\User\TodoBalanceLog;

class RecalcTodoBalanceJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, Queueable;

    public int $uniqueFor = 10;

    public function __construct(private int $todoBalanceId) {}

    public function uniqueId(): int
    {
        return $this->todoBalanceId;
    }

    public function handle(): void
    {
        $balance = TodoBalance::find($this->todoBalanceId);
        if (! $balance) {
            return;
        }

        $logs = TodoBalanceLog::where('todo_balance_id', $balance->id)
            ->orderBy('occurred_on')
            ->orderBy('id')
            ->get();

        $running = 0.0;
        foreach ($logs as $log) {
            $before = round($running, 4);
            $running = round($running + (float) $log->delta, 4);

            if ((float) $log->balance_before !== $before || (float) $log->balance_after !== $running) {
                $log->balance_before = $before;
                $log->balance_after = $running;
                $log->saveQuietly();
            }
        }

        $finalBalance = round($running, 4);
        if ((float) $balance->balance !== $finalBalance) {
            $balance->balance = $finalBalance;
            $balance->saveQuietly();
        }
    }
}
