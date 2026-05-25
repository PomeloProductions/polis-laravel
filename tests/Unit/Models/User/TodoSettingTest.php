<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Models\User;

use App\Models\User\TodoSetting;
use Polis\Contracts\Models\HasValidationRulesContract;
use Polis\Tests\TestCase;

final class TodoSettingTest extends TestCase
{
    public function test_user(): void
    {
        $model = new TodoSetting;
        $relation = $model->user();

        $this->assertEquals('todo_settings.user_id', $relation->getQualifiedForeignKeyName());
        $this->assertEquals('users.id', $relation->getQualifiedOwnerKeyName());
    }

    public function test_casts(): void
    {
        $model = new TodoSetting;
        $casts = $model->getCasts();

        $this->assertEquals('integer', $casts['week_start_day']);
    }

    public function test_validation_rules_base(): void
    {
        $model = new TodoSetting;
        $rules = $model->buildModelValidationRules();

        $this->assertArrayHasKey(HasValidationRulesContract::VALIDATION_RULES_BASE, $rules);
        $baseRules = $rules[HasValidationRulesContract::VALIDATION_RULES_BASE];

        $this->assertArrayHasKey('week_start_day', $baseRules);
    }

    public function test_validation_rules_create(): void
    {
        $model = new TodoSetting;
        $rules = $model->buildModelValidationRules();

        $this->assertArrayHasKey(HasValidationRulesContract::VALIDATION_RULES_CREATE, $rules);
    }
}
