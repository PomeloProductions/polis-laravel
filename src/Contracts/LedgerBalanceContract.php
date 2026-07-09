<?php

declare(strict_types=1);

namespace Polis\Contracts;

use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A running-tally ledger keyed by a stable string key, backed by an append-only
 * log of deltas. This is the generic extraction of Todo's TodoBalance: a single
 * `balance` value maintained by summing an ordered chain of log entries, each of
 * which records a delta and the before/after snapshot, and may point
 * polymorphically at whatever caused the change (a time entry, a manual edit …).
 *
 * The concrete `balance` column and the log model are supplied by the
 * implementer; this contract fixes the shape the ledger tooling relies on.
 *
 * @property string $item_key The stable key this ledger tracks.
 * @property float $balance The current running tally.
 */
interface LedgerBalanceContract
{
    /**
     * The append-only log entries backing this balance, in creation order.
     */
    public function logs(): HasMany;

    /**
     * The stable key that identifies what this ledger tracks (Todo derives it
     * from a task label; another domain might use a SKU, an account id, …).
     */
    public function ledgerKey(): string;

    /**
     * The current running tally.
     */
    public function currentBalance(): float;

    /**
     * The name of the foreign-key column on the log model that points back at a
     * balance row (e.g. `todo_balance_id`). Used by recalculation tooling to
     * walk a balance's log chain.
     */
    public function ledgerLogForeignKey(): string;
}
