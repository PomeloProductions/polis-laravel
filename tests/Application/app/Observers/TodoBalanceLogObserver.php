<?php

declare(strict_types=1);

namespace App\Observers;

use App\Jobs\RecalcTodoBalanceJob;
use App\Models\User\TodoBalanceLog;

class TodoBalanceLogObserver
{
    public function created(TodoBalanceLog $log): void
    {
        RecalcTodoBalanceJob::dispatch($log->todo_balance_id);
    }

    public function updated(TodoBalanceLog $log): void
    {
        RecalcTodoBalanceJob::dispatch($log->todo_balance_id);
    }

    public function deleted(TodoBalanceLog $log): void
    {
        RecalcTodoBalanceJob::dispatch($log->todo_balance_id);
    }
}
