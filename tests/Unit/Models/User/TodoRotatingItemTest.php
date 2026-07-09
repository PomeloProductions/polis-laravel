<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Models\User;

use Polis\Contracts\Models\HasValidationRulesContract;
use Polis\Models\User\TodoRotatingItem;
use Polis\Tests\TestCase;

final class TodoRotatingItemTest extends TestCase
{
    public function test_group_relationship(): void
    {
        $model = new TodoRotatingItem;
        $relation = $model->group();

        $this->assertEquals('todo_rotating_items.todo_rotating_group_id', $relation->getQualifiedForeignKeyName());
    }

    public function test_casts(): void
    {
        $model = new TodoRotatingItem;
        $casts = $model->getCasts();

        $this->assertEquals('integer', $casts['count']);
        $this->assertEquals('integer', $casts['sort_order']);
    }

    public function test_validation_rules(): void
    {
        $model = new TodoRotatingItem;
        $rules = $model->buildModelValidationRules();

        $base = $rules[HasValidationRulesContract::VALIDATION_RULES_BASE];
        $this->assertArrayHasKey('client_id', $base);
        $this->assertArrayHasKey('text', $base);
        $this->assertArrayHasKey('last_date', $base);
        $this->assertArrayHasKey('on_copy', $base);
        $this->assertArrayHasKey('count', $base);

        $create = $rules[HasValidationRulesContract::VALIDATION_RULES_CREATE];
        $required = $create[HasValidationRulesContract::VALIDATION_PREPEND_REQUIRED];
        $this->assertContains('client_id', $required);
        $this->assertContains('text', $required);
    }
}
