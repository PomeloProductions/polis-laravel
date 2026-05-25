<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Models\User;

use App\Models\User\TodoSubItem;
use Polis\Contracts\Models\HasValidationRulesContract;
use Polis\Tests\TestCase;

final class TodoSubItemTest extends TestCase
{
    public function test_task_node_relationship(): void
    {
        $model = new TodoSubItem;
        $relation = $model->taskNode();

        $this->assertEquals('todo_sub_items.todo_task_node_id', $relation->getQualifiedForeignKeyName());
    }

    public function test_casts(): void
    {
        $model = new TodoSubItem;
        $casts = $model->getCasts();

        $this->assertEquals('boolean', $casts['completed']);
        $this->assertEquals('integer', $casts['sort_order']);
    }

    public function test_validation_rules(): void
    {
        $model = new TodoSubItem;
        $rules = $model->buildModelValidationRules();

        $base = $rules[HasValidationRulesContract::VALIDATION_RULES_BASE];
        $this->assertArrayHasKey('client_id', $base);
        $this->assertArrayHasKey('text', $base);
        $this->assertArrayHasKey('completed', $base);

        $create = $rules[HasValidationRulesContract::VALIDATION_RULES_CREATE];
        $required = $create[HasValidationRulesContract::VALIDATION_PREPEND_REQUIRED];
        $this->assertContains('client_id', $required);
        $this->assertContains('text', $required);
    }
}
