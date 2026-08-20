<?php

declare(strict_types=1);

namespace Polis\Observers;

use Polis\Jobs\RecalcTodoBalanceJob;
use Polis\Models\User\TodoBalanceLog;

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
