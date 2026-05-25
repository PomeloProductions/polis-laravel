<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Models\User;

use App\Models\User\UserPage;
use Polis\Contracts\Models\HasValidationRulesContract;
use Polis\Tests\TestCase;

final class UserPageTest extends TestCase
{
    public function test_user(): void
    {
        $page = new UserPage;
        $relation = $page->user();

        $this->assertEquals('user_pages.user_id', $relation->getQualifiedForeignKeyName());
        $this->assertEquals('users.id', $relation->getQualifiedOwnerKeyName());
    }

    public function test_parent_page(): void
    {
        $page = new UserPage;
        $relation = $page->parentPage();

        $this->assertEquals('user_pages.parent_page_id', $relation->getQualifiedForeignKeyName());
        $this->assertEquals('user_pages.id', $relation->getQualifiedOwnerKeyName());
    }

    public function test_child_pages(): void
    {
        $page = new UserPage;
        $relation = $page->childPages();

        $this->assertEquals('user_pages.parent_page_id', $relation->getQualifiedForeignKeyName());
        $this->assertEquals('user_pages.id', $relation->getQualifiedParentKeyName());
    }

    public function test_components(): void
    {
        $page = new UserPage;
        $relation = $page->components();

        $this->assertEquals('user_page_components.user_page_id', $relation->getQualifiedForeignKeyName());
        $this->assertEquals('user_pages.id', $relation->getQualifiedParentKeyName());
    }

    public function test_valid_page_types(): void
    {
        $this->assertContains('dashboard', UserPage::VALID_PAGE_TYPES);
        $this->assertContains('list', UserPage::VALID_PAGE_TYPES);
        $this->assertContains('detail', UserPage::VALID_PAGE_TYPES);
        $this->assertContains('todo', UserPage::VALID_PAGE_TYPES);
        $this->assertCount(4, UserPage::VALID_PAGE_TYPES);
    }

    public function test_valid_component_types(): void
    {
        $this->assertContains('day_summary', UserPage::VALID_COMPONENT_TYPES);
        $this->assertContains('stats_cards', UserPage::VALID_COMPONENT_TYPES);
        $this->assertContains('page_manager', UserPage::VALID_COMPONENT_TYPES);
        $this->assertContains('settings_panel', UserPage::VALID_COMPONENT_TYPES);
        $this->assertContains('todo_task', UserPage::VALID_COMPONENT_TYPES);
        $this->assertContains('todo_bullet_list', UserPage::VALID_COMPONENT_TYPES);
        $this->assertCount(6, UserPage::VALID_COMPONENT_TYPES);
    }

    public function test_casts(): void
    {
        $page = new UserPage;
        $casts = $page->getCasts();

        $this->assertEquals('integer', $casts['display_order']);
        $this->assertEquals('boolean', $casts['is_visible']);
        $this->assertEquals('boolean', $casts['is_required']);
        $this->assertEquals('boolean', $casts['is_nav_item']);
        $this->assertEquals('array', $casts['config_json']);
    }

    public function test_validation_rules_base(): void
    {
        $page = new UserPage;
        $rules = $page->buildModelValidationRules();

        $this->assertArrayHasKey(HasValidationRulesContract::VALIDATION_RULES_BASE, $rules);
        $baseRules = $rules[HasValidationRulesContract::VALIDATION_RULES_BASE];

        $this->assertArrayHasKey('slug', $baseRules);
        $this->assertArrayHasKey('name', $baseRules);
        $this->assertArrayHasKey('icon', $baseRules);
        $this->assertArrayHasKey('color', $baseRules);
        $this->assertArrayHasKey('route_path', $baseRules);
        $this->assertArrayHasKey('page_type', $baseRules);
        $this->assertArrayHasKey('display_order', $baseRules);
        $this->assertArrayHasKey('is_visible', $baseRules);
        $this->assertArrayHasKey('is_nav_item', $baseRules);
        $this->assertArrayHasKey('parent_page_id', $baseRules);
        $this->assertArrayHasKey('config_json', $baseRules);
    }

    public function test_validation_rules_create_requires_fields(): void
    {
        $page = new UserPage;
        $rules = $page->buildModelValidationRules();

        $this->assertArrayHasKey(HasValidationRulesContract::VALIDATION_RULES_CREATE, $rules);
        $createRules = $rules[HasValidationRulesContract::VALIDATION_RULES_CREATE];

        $this->assertArrayHasKey(HasValidationRulesContract::VALIDATION_PREPEND_REQUIRED, $createRules);
        $this->assertContains('name', $createRules[HasValidationRulesContract::VALIDATION_PREPEND_REQUIRED]);
        $this->assertContains('route_path', $createRules[HasValidationRulesContract::VALIDATION_PREPEND_REQUIRED]);
        $this->assertContains('page_type', $createRules[HasValidationRulesContract::VALIDATION_PREPEND_REQUIRED]);
    }
}
