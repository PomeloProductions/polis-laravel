<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Models\User;

use Polis\Contracts\LedgerBalanceContract;
use Polis\Contracts\Models\HasNodeTreeContract;
use Polis\Models\User\TodoBalance;
use Polis\Models\User\TodoTaskNode;
use Polis\Tests\TestCase;

/**
 * Verifies the Todo models correctly implement the generic framework contracts.
 */
final class TodoTaskNodeTreeContractTest extends TestCase
{
    public function test_task_node_is_a_node_tree(): void
    {
        $node = new TodoTaskNode;

        $this->assertInstanceOf(HasNodeTreeContract::class, $node);
        $this->assertEquals('parent_id', $node->nodeParentColumn());
        $this->assertEquals('sort_order', $node->nodeSortColumn());
        $this->assertEquals('user_page_component_id', $node->nodeScopeColumn());
    }

    public function test_task_node_tree_relations(): void
    {
        $node = new TodoTaskNode;

        $this->assertEquals('todo_task_nodes.parent_id', $node->parent()->getQualifiedForeignKeyName());
        $this->assertEquals('todo_task_nodes.parent_id', $node->children()->getQualifiedForeignKeyName());
    }

    public function test_balance_is_a_ledger(): void
    {
        $balance = new TodoBalance;
        $balance->item_key = 'coding';
        $balance->balance = 12.5;

        $this->assertInstanceOf(LedgerBalanceContract::class, $balance);
        $this->assertEquals('coding', $balance->ledgerKey());
        $this->assertEqualsWithDelta(12.5, $balance->currentBalance(), 0.0001);
        $this->assertEquals('todo_balance_id', $balance->ledgerLogForeignKey());
        $this->assertEquals('todo_balance_logs.todo_balance_id', $balance->logs()->getQualifiedForeignKeyName());
    }
}
