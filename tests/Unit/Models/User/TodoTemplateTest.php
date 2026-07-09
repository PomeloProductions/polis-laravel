<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Models\User;

use Polis\Contracts\Models\HasValidationRulesContract;
use Polis\Models\User\TodoTemplate;
use Polis\Tests\TestCase;

final class TodoTemplateTest extends TestCase
{
    public function test_user(): void
    {
        $model = new TodoTemplate;
        $relation = $model->user();

        $this->assertEquals('todo_templates.user_id', $relation->getQualifiedForeignKeyName());
        $this->assertEquals('users.id', $relation->getQualifiedOwnerKeyName());
    }

    public function test_valid_levels(): void
    {
        $this->assertContains('year', TodoTemplate::VALID_LEVELS);
        $this->assertContains('month', TodoTemplate::VALID_LEVELS);
        $this->assertContains('week', TodoTemplate::VALID_LEVELS);
        $this->assertContains('day', TodoTemplate::VALID_LEVELS);
        $this->assertCount(4, TodoTemplate::VALID_LEVELS);
    }

    public function test_casts(): void
    {
        $model = new TodoTemplate;
        $casts = $model->getCasts();

        $this->assertEquals('array', $casts['sections_json']);
    }

    public function test_validation_rules_base(): void
    {
        $model = new TodoTemplate;
        $rules = $model->buildModelValidationRules();

        $this->assertArrayHasKey(HasValidationRulesContract::VALIDATION_RULES_BASE, $rules);
        $baseRules = $rules[HasValidationRulesContract::VALIDATION_RULES_BASE];

        $this->assertArrayHasKey('name', $baseRules);
        $this->assertArrayHasKey('level', $baseRules);
        $this->assertArrayHasKey('sections_json', $baseRules);
    }

    public function test_validation_rules_create_requires_fields(): void
    {
        $model = new TodoTemplate;
        $rules = $model->buildModelValidationRules();

        $this->assertArrayHasKey(HasValidationRulesContract::VALIDATION_RULES_CREATE, $rules);
        $createRules = $rules[HasValidationRulesContract::VALIDATION_RULES_CREATE];

        $this->assertArrayHasKey(HasValidationRulesContract::VALIDATION_PREPEND_REQUIRED, $createRules);
        $this->assertContains('name', $createRules[HasValidationRulesContract::VALIDATION_PREPEND_REQUIRED]);
        $this->assertContains('level', $createRules[HasValidationRulesContract::VALIDATION_PREPEND_REQUIRED]);
        $this->assertContains('sections_json', $createRules[HasValidationRulesContract::VALIDATION_PREPEND_REQUIRED]);
    }
}
