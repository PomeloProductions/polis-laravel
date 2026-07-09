<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Models\User;

use Polis\Contracts\Models\HasValidationRulesContract;
use Polis\Models\User\TodoRotatingGroup;
use Polis\Tests\TestCase;

final class TodoRotatingGroupTest extends TestCase
{
    public function test_task_node_relationship(): void
    {
        $model = new TodoRotatingGroup;
        $relation = $model->taskNode();

        $this->assertEquals('todo_rotating_groups.todo_task_node_id', $relation->getQualifiedForeignKeyName());
    }

    public function test_child_nodes_relationship(): void
    {
        // The category-tree-shape refactor replaced the old parentGroup/subGroups/items
        // relations with a single `childNodes` HasMany of TodoTaskNode keyed on
        // todo_rotating_group_id.
        $model = new TodoRotatingGroup;
        $relation = $model->childNodes();

        $this->assertEquals('todo_task_nodes.todo_rotating_group_id', $relation->getQualifiedForeignKeyName());
    }

    public function test_casts(): void
    {
        $model = new TodoRotatingGroup;
        $casts = $model->getCasts();

        $this->assertEquals('integer', $casts['group_number']);
        $this->assertEquals('integer', $casts['count_this_group']);
        $this->assertEquals('boolean', $casts['mark_done_on_group']);
        $this->assertEquals('integer', $casts['sort_order']);
    }

    public function test_validation_rules(): void
    {
        $model = new TodoRotatingGroup;
        $rules = $model->buildModelValidationRules();

        $base = $rules[HasValidationRulesContract::VALIDATION_RULES_BASE];
        $this->assertArrayHasKey('group_number', $base);
        $this->assertArrayHasKey('label', $base);
        $this->assertArrayHasKey('count_this_group', $base);
        $this->assertArrayHasKey('on_copy', $base);
        $this->assertArrayHasKey('mark_done_on_group', $base);
    }
}
