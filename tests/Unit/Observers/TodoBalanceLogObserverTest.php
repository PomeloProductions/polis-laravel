<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Observers;

use Illuminate\Support\Facades\Bus;
use Polis\Jobs\RecalcTodoBalanceJob;
use Polis\Models\User\TodoBalanceLog;
use Polis\Observers\TodoBalanceLogObserver;
use Polis\Tests\TestCase;

/**
 * TodoBalanceLogObserver — every log mutation (created/updated/deleted)
 * dispatches a RecalcTodoBalanceJob keyed on the log's balance id, so the
 * authoritative log replay heals the balance after any write.
 */
final class TodoBalanceLogObserverTest extends TestCase
{
    private function assertDispatchesRecalcForBalance(int $balanceId): void
    {
        Bus::assertDispatched(
            RecalcTodoBalanceJob::class,
            fn (RecalcTodoBalanceJob $job) => $job->uniqueId() === $balanceId,
        );
    }

    public function test_created_dispatches_recalc_job_for_the_logs_balance(): void
    {
        Bus::fake();

        (new TodoBalanceLogObserver)->created(new TodoBalanceLog(['todo_balance_id' => 7]));

        $this->assertDispatchesRecalcForBalance(7);
    }

    public function test_updated_dispatches_recalc_job_for_the_logs_balance(): void
    {
        Bus::fake();

        (new TodoBalanceLogObserver)->updated(new TodoBalanceLog(['todo_balance_id' => 8]));

        $this->assertDispatchesRecalcForBalance(8);
    }

    public function test_deleted_dispatches_recalc_job_for_the_logs_balance(): void
    {
        Bus::fake();

        (new TodoBalanceLogObserver)->deleted(new TodoBalanceLog(['todo_balance_id' => 9]));

        $this->assertDispatchesRecalcForBalance(9);
    }
}
