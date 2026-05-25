<?php

declare(strict_types=1);

namespace Test\Athenia\Unit\Models\Traits;

use Polis\Contracts\Models\HasValidationRulesContract;
use Polis\Models\Traits\HasValidationRules;
use Polis\Tests\TestCase;

/**
 * Class HasValidationRulesTest
 */
class HasValidationRulesTest extends TestCase
{
    public function test_base_not_set()
    {
        $model = new class implements HasValidationRulesContract
        {
            use HasValidationRules;

            public function buildModelValidationRules(...$params): array
            {
                return [];
            }
        };
        $this->assertEmpty($model->getValidationRules());
    }

    public function test_get_simple_rules()
    {
        $model = new class implements HasValidationRulesContract
        {
            use HasValidationRules;

            public function buildModelValidationRules(...$params): array
            {
                return [
                    HasValidationRulesContract::VALIDATION_RULES_BASE => ['hi'],
                ];
            }
        };
        $this->assertEquals(['hi'], $model->getValidationRules());
    }

    public function test_context_set_but_does_not_exist()
    {
        $model = new class implements HasValidationRulesContract
        {
            use HasValidationRules;

            public function buildModelValidationRules(...$params): array
            {
                return [
                    HasValidationRulesContract::VALIDATION_RULES_BASE => ['hi'],
                ];
            }
        };
        $this->assertEquals(['hi'], $model->getValidationRules('notExist'));
    }

    public function test_context_set_but_does_not_match()
    {
        $model = new class implements HasValidationRulesContract
        {
            use HasValidationRules;

            public function buildModelValidationRules(...$params): array
            {
                return [
                    HasValidationRulesContract::VALIDATION_RULES_BASE => ['hi' => 'there'],
                    'context-here' => ['prepend-non' => ['here']],
                ];
            }
        };
        $this->assertEquals(['hi' => 'there'], $model->getValidationRules('context-here'));
    }

    public function test_context_prepends()
    {
        $model = new class implements HasValidationRulesContract
        {
            use HasValidationRules;

            public function buildModelValidationRules(...$params): array
            {
                return [
                    HasValidationRulesContract::VALIDATION_RULES_BASE => ['property_name' => ['integer']],
                    'update-context' => ['prepend-required' => ['property_name']],
                ];
            }
        };
        $this->assertEquals(['property_name' => ['required', 'integer']], $model->getValidationRules('update-context'));
    }
}
