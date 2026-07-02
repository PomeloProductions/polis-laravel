<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Models\User;

use Polis\Contracts\Models\HasValidationRulesContract;
use Polis\Models\User\TodoTaskNode;
use Polis\Tests\TestCase;

final class TodoTaskNodeTest extends TestCase
{
    public function test_component_relationship(): void
    {
        $model = new TodoTaskNode;
        $relation = $model->component();

        $this->assertEquals('todo_task_nodes.user_page_component_id', $relation->getQualifiedForeignKeyName());
    }

    public function test_parent_relationship(): void
    {
        $model = new TodoTaskNode;
        $relation = $model->parent();

        $this->assertEquals('todo_task_nodes.parent_id', $relation->getQualifiedForeignKeyName());
    }

    public function test_children_relationship(): void
    {
        $model = new TodoTaskNode;
        $relation = $model->children();

        $this->assertEquals('todo_task_nodes.parent_id', $relation->getQualifiedForeignKeyName());
    }

    public function test_groups_relationship(): void
    {
        $model = new TodoTaskNode;
        $relation = $model->groups();

        $this->assertEquals('todo_rotating_groups.todo_task_node_id', $relation->getQualifiedForeignKeyName());
    }

    public function test_sub_items_relationship(): void
    {
        $model = new TodoTaskNode;
        $relation = $model->subItems();

        $this->assertEquals('todo_sub_items.todo_task_node_id', $relation->getQualifiedForeignKeyName());
    }

    public function test_todo_balance_relationship(): void
    {
        $model = new TodoTaskNode;
        $relation = $model->todoBalance();

        $this->assertEquals('todo_task_nodes.todo_balance_id', $relation->getQualifiedForeignKeyName());
    }

    public function test_casts(): void
    {
        $model = new TodoTaskNode;
        $casts = $model->getCasts();

        $this->assertEquals('integer', $casts['sort_order']);
        $this->assertEquals('boolean', $casts['collapsed']);
        $this->assertEquals('boolean', $casts['completed']);
        $this->assertEquals('boolean', $casts['custom_groups']);
        $this->assertEquals('boolean', $casts['decrement_on_done']);
        $this->assertEquals('array', $casts['schedule']);
        $this->assertEquals('integer', $casts['cascade_ratio']);
    }

    public function test_constants(): void
    {
        $this->assertEquals('category', TodoTaskNode::TASK_TYPE_CATEGORY);
        $this->assertEquals('rotating', TodoTaskNode::TASK_TYPE_ROTATING);
        $this->assertEquals('line_item', TodoTaskNode::TASK_TYPE_LINE_ITEM);
    }

    public function test_validation_rules_base(): void
    {
        $model = new TodoTaskNode;
        $rules = $model->buildModelValidationRules();

        $this->assertArrayHasKey(HasValidationRulesContract::VALIDATION_RULES_BASE, $rules);
        $base = $rules[HasValidationRulesContract::VALIDATION_RULES_BASE];

        $this->assertArrayHasKey('client_id', $base);
        $this->assertArrayHasKey('task_type', $base);
        $this->assertArrayHasKey('label', $base);
        $this->assertArrayHasKey('tally', $base);
        $this->assertArrayHasKey('schedule', $base);
        $this->assertArrayHasKey('tracking_mode', $base);
        $this->assertArrayHasKey('cascade_ratio', $base);
    }

    public function test_validation_rules_create(): void
    {
        $model = new TodoTaskNode;
        $rules = $model->buildModelValidationRules();

        $this->assertArrayHasKey(HasValidationRulesContract::VALIDATION_RULES_CREATE, $rules);
        $create = $rules[HasValidationRulesContract::VALIDATION_RULES_CREATE];
        $required = $create[HasValidationRulesContract::VALIDATION_PREPEND_REQUIRED];

        $this->assertContains('client_id', $required);
        $this->assertContains('task_type', $required);
    }
}
