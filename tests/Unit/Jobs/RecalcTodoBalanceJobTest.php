<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Jobs;

use Polis\Jobs\RecalcTodoBalanceJob;
use Polis\Models\User\TodoBalance;
use Polis\Models\User\TodoBalanceLog;
use Polis\Tests\TestCase;
use Polis\Tests\Traits\CreatesTodoModuleSchema;

/**
 * RecalcTodoBalanceJob replays a balance's full log in (occurred_on, id)
 * order, rewriting any incoherent balance_before/balance_after values and
 * snapping the balance itself to the replayed total. The log is authoritative
 * for hours-mode balances — this job is the self-healing mechanism.
 */
final class RecalcTodoBalanceJobTest extends TestCase
{
    use CreatesTodoModuleSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createTodoModuleTables();
    }

    protected function tearDown(): void
    {
        $this->dropTodoModuleTables();
        parent::tearDown();
    }

    private function makeBalance(array $attrs = []): TodoBalance
    {
        return TodoBalance::create(array_merge([
            'user_id' => 1,
            'item_key' => 'item-'.uniqid(),
            'tracking_mode' => 'hours',
            'balance' => 0,
        ], $attrs));
    }

    private function makeLog(TodoBalance $balance, array $attrs = []): TodoBalanceLog
    {
        return TodoBalanceLog::create(array_merge([
            'user_id' => $balance->user_id,
            'todo_balance_id' => $balance->id,
            'reason' => TodoBalanceLog::REASON_DAILY_INCREMENT,
            'delta' => 0,
            'balance_before' => 0,
            'balance_after' => 0,
            'occurred_on' => '2026-07-01',
        ], $attrs));
    }

    public function test_replays_log_chain_and_rewrites_incoherent_before_after_values(): void
    {
        $balance = $this->makeBalance(['balance' => 999]); // drifted snapshot

        // Deliberately corrupted chain; replay order is occurred_on then id.
        $seed = $this->makeLog($balance, ['reason' => TodoBalanceLog::REASON_SEED, 'delta' => -5, 'occurred_on' => '2026-07-01', 'balance_before' => 7, 'balance_after' => 7]);
        $increment = $this->makeLog($balance, ['delta' => 1.5, 'occurred_on' => '2026-07-02', 'balance_before' => 7, 'balance_after' => 7]);
        $logged = $this->makeLog($balance, ['reason' => TodoBalanceLog::REASON_TIMER_LOGGED, 'delta' => -0.25, 'occurred_on' => '2026-07-03', 'balance_before' => 7, 'balance_after' => 7]);

        (new RecalcTodoBalanceJob($balance->id))->handle();

        $seed->refresh();
        $this->assertEqualsWithDelta(0.0, (float) $seed->balance_before, 0.0001);
        $this->assertEqualsWithDelta(-5.0, (float) $seed->balance_after, 0.0001);

        $increment->refresh();
        $this->assertEqualsWithDelta(-5.0, (float) $increment->balance_before, 0.0001);
        $this->assertEqualsWithDelta(-3.5, (float) $increment->balance_after, 0.0001);

        $logged->refresh();
        $this->assertEqualsWithDelta(-3.5, (float) $logged->balance_before, 0.0001);
        $this->assertEqualsWithDelta(-3.75, (float) $logged->balance_after, 0.0001);

        $this->assertEqualsWithDelta(-3.75, (float) $balance->refresh()->balance, 0.0001);
    }

    public function test_same_day_logs_replay_in_id_order(): void
    {
        $balance = $this->makeBalance();
        $first = $this->makeLog($balance, ['delta' => 2, 'occurred_on' => '2026-07-05']);
        $second = $this->makeLog($balance, ['delta' => -0.5, 'occurred_on' => '2026-07-05']);

        (new RecalcTodoBalanceJob($balance->id))->handle();

        $this->assertEqualsWithDelta(2.0, (float) $first->refresh()->balance_after, 0.0001);
        $this->assertEqualsWithDelta(2.0, (float) $second->refresh()->balance_before, 0.0001);
        $this->assertEqualsWithDelta(1.5, (float) $second->balance_after, 0.0001);
        $this->assertEqualsWithDelta(1.5, (float) $balance->refresh()->balance, 0.0001);
    }

    public function test_empty_log_resets_balance_to_zero(): void
    {
        $balance = $this->makeBalance(['balance' => 12.5]);

        (new RecalcTodoBalanceJob($balance->id))->handle();

        $this->assertEqualsWithDelta(0.0, (float) $balance->refresh()->balance, 0.0001);
    }

    public function test_missing_balance_is_a_noop(): void
    {
        $balance = $this->makeBalance();
        $log = $this->makeLog($balance, ['delta' => 3, 'balance_before' => 42, 'balance_after' => 42]);

        (new RecalcTodoBalanceJob(999999))->handle();

        // Nothing was replayed: the (wrong) values on the unrelated log survive.
        $this->assertEqualsWithDelta(42.0, (float) $log->refresh()->balance_after, 0.0001);
    }

    public function test_unique_id_is_the_balance_id(): void
    {
        // ShouldBeUnique keys on the balance id so concurrent recalcs of the
        // same balance coalesce while different balances run independently.
        $this->assertSame(17, (new RecalcTodoBalanceJob(17))->uniqueId());
    }
}
