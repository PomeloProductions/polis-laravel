<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Models\User;

use App\Models\User\UserPageComponent;
use Polis\Contracts\Models\HasValidationRulesContract;
use Polis\Models\User\UserPage;
use Polis\Tests\TestCase;

final class UserPageComponentTest extends TestCase
{
    public function test_page(): void
    {
        $component = new UserPageComponent;
        $relation = $component->page();

        $this->assertEquals('user_page_components.user_page_id', $relation->getQualifiedForeignKeyName());
        $this->assertEquals('user_pages.id', $relation->getQualifiedOwnerKeyName());
    }

    public function test_casts(): void
    {
        $component = new UserPageComponent;
        $casts = $component->getCasts();

        $this->assertEquals('integer', $casts['display_order']);
        $this->assertEquals('array', $casts['config_json']);
    }

    public function test_validation_rules_base(): void
    {
        $component = new UserPageComponent;
        $rules = $component->buildModelValidationRules();

        $this->assertArrayHasKey(HasValidationRulesContract::VALIDATION_RULES_BASE, $rules);
        $baseRules = $rules[HasValidationRulesContract::VALIDATION_RULES_BASE];

        $this->assertArrayHasKey('component_type', $baseRules);
        $this->assertArrayHasKey('display_order', $baseRules);
        $this->assertArrayHasKey('config_json', $baseRules);
    }

    public function test_validation_rules_component_type_validates_against_valid_types(): void
    {
        $component = new UserPageComponent;
        $rules = $component->buildModelValidationRules();

        $componentTypeRules = $rules[HasValidationRulesContract::VALIDATION_RULES_BASE]['component_type'];

        $inRule = collect($componentTypeRules)->first(function ($rule) {
            return is_string($rule) && str_starts_with($rule, 'in:');
        });

        $this->assertNotNull($inRule);

        foreach (UserPage::VALID_COMPONENT_TYPES as $type) {
            $this->assertStringContainsString($type, $inRule);
        }
    }

    public function test_validation_rules_create_requires_component_type(): void
    {
        $component = new UserPageComponent;
        $rules = $component->buildModelValidationRules();

        $this->assertArrayHasKey(HasValidationRulesContract::VALIDATION_RULES_CREATE, $rules);
        $createRules = $rules[HasValidationRulesContract::VALIDATION_RULES_CREATE];

        $this->assertArrayHasKey(HasValidationRulesContract::VALIDATION_PREPEND_REQUIRED, $createRules);
        $this->assertContains('component_type', $createRules[HasValidationRulesContract::VALIDATION_PREPEND_REQUIRED]);
    }
}
