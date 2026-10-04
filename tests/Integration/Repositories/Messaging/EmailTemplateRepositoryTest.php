<?php

declare(strict_types=1);

namespace Polis\Tests\Integration\Repositories\Messaging;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Polis\Models\Messaging\EmailTemplate;
use Polis\Repositories\Messaging\EmailTemplateRepository;
use Polis\Tests\Application\ApplicationTestCase;
use Polis\Tests\Traits\MocksApplicationLog;

/**
 * Integration tests for EmailTemplateRepository.
 *
 * Exercises all methods against a real SQLite database (the dummy consumer
 * app schema + package migrations). Mirrors the pattern established in
 * MessageRepositoryTest and ThreadRepositoryTest.
 */
final class EmailTemplateRepositoryTest extends ApplicationTestCase
{
    use MocksApplicationLog;

    private EmailTemplateRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();

        $this->repository = new EmailTemplateRepository(
            new EmailTemplate,
            $this->getGenericLogMock(),
        );
    }

    // ─── helpers ────────────────────────────────────────────────────────────

    private function insertTemplate(array $attributes): EmailTemplate
    {
        return EmailTemplate::query()->create(array_merge([
            'title' => 'Default Title',
            'created_by_id' => 1,
        ], $attributes));
    }

    private function clearTemplates(): void
    {
        foreach (EmailTemplate::withoutGlobalScopes()->get() as $template) {
            $template->forceDelete();
        }
    }

    // ─── findByKey ──────────────────────────────────────────────────────────

    public function test_find_by_key_returns_null_when_no_template_exists(): void
    {
        $this->assertNull($this->repository->findByKey('missing_key'));
    }

    public function test_find_by_key_returns_global_template_when_org_id_is_null(): void
    {
        $template = $this->insertTemplate([
            'key' => 'welcome',
            'title' => 'Global Welcome',
            'organization_id' => null,
        ]);

        $found = $this->repository->findByKey('welcome');

        $this->assertNotNull($found);
        $this->assertSame($template->id, $found->id);
        $this->assertSame('Global Welcome', $found->title);
    }

    public function test_find_by_key_returns_org_scoped_override_when_present(): void
    {
        $global = $this->insertTemplate([
            'key' => 'welcome',
            'title' => 'Global Welcome',
            'organization_id' => null,
        ]);
        $orgOverride = $this->insertTemplate([
            'key' => 'welcome',
            'title' => 'Org Welcome',
            'organization_id' => 42,
        ]);

        $found = $this->repository->findByKey('welcome', 42);

        $this->assertSame($orgOverride->id, $found->id);
        $this->assertNotEquals($global->id, $found->id);
    }

    public function test_find_by_key_falls_back_to_global_when_org_scoped_missing(): void
    {
        $global = $this->insertTemplate([
            'key' => 'welcome',
            'title' => 'Global Welcome',
            'organization_id' => null,
        ]);
        // org 99 has an override, but not org 42
        $this->insertTemplate([
            'key' => 'welcome',
            'title' => 'Org 99 Welcome',
            'organization_id' => 99,
        ]);

        $found = $this->repository->findByKey('welcome', 42);

        $this->assertSame($global->id, $found->id);
    }

    public function test_find_by_key_returns_null_when_neither_org_nor_global_exist(): void
    {
        // Only org 99 exists — requesting org 42 should return null (no global)
        $this->insertTemplate([
            'key' => 'welcome',
            'title' => 'Org 99 Welcome',
            'organization_id' => 99,
        ]);

        $this->assertNull($this->repository->findByKey('welcome', 42));
    }

    public function test_find_by_key_returns_latest_global_by_updated_at(): void
    {
        $older = $this->insertTemplate(['key' => 'renewal', 'title' => 'Older Renewal']);
        $older->updated_at = now()->subDay();
        $older->save();

        $newer = $this->insertTemplate(['key' => 'renewal', 'title' => 'Newer Renewal']);
        $newer->updated_at = now();
        $newer->save();

        $found = $this->repository->findByKey('renewal');

        $this->assertSame($newer->id, $found->id);
    }

    public function test_find_by_key_returns_org_scoped_even_when_global_is_newer(): void
    {
        // Global template updated very recently; org-scoped override is older.
        // findByKey() must still prefer the org-scoped row (priority beats recency).
        $global = $this->insertTemplate([
            'key' => 'renewal',
            'title' => 'Global Renewal',
            'organization_id' => null,
        ]);
        $global->updated_at = now();
        $global->save();

        $orgScoped = $this->insertTemplate([
            'key' => 'renewal',
            'title' => 'Org Renewal',
            'organization_id' => 5,
        ]);
        $orgScoped->updated_at = now()->subDay();
        $orgScoped->save();

        $found = $this->repository->findByKey('renewal', 5);

        $this->assertSame($orgScoped->id, $found->id);
        $this->assertSame('Org Renewal', $found->title);
    }

    // ─── findOrgScopedByKey ─────────────────────────────────────────────────

    public function test_find_org_scoped_by_key_returns_org_row(): void
    {
        $this->insertTemplate(['key' => 'onboarding', 'title' => 'Global Onboarding']);
        $orgTemplate = $this->insertTemplate([
            'key' => 'onboarding',
            'title' => 'Org Onboarding',
            'organization_id' => 7,
        ]);

        $found = $this->repository->findOrgScopedByKey('onboarding', 7);

        $this->assertNotNull($found);
        $this->assertSame($orgTemplate->id, $found->id);
    }

    public function test_find_org_scoped_by_key_returns_null_when_only_global_exists(): void
    {
        $this->insertTemplate(['key' => 'onboarding', 'title' => 'Global Onboarding']);

        $found = $this->repository->findOrgScopedByKey('onboarding', 7);

        $this->assertNull($found);
    }

    // ─── upsertOrgScoped ────────────────────────────────────────────────────

    public function test_upsert_org_scoped_creates_when_absent(): void
    {
        $result = $this->repository->upsertOrgScoped('digest', 10, 'Daily Digest', '<p>Hello</p>');

        $this->assertNotNull($result->id);
        $this->assertSame('Daily Digest', $result->title);
        $this->assertSame('digest', $result->key);
        $this->assertSame(10, (int) $result->organization_id);
    }

    public function test_upsert_org_scoped_updates_existing_title(): void
    {
        $this->insertTemplate([
            'key' => 'digest',
            'title' => 'Old Title',
            'organization_id' => 10,
        ]);

        $result = $this->repository->upsertOrgScoped('digest', 10, 'New Title', '<p>Updated</p>');

        $this->assertSame('New Title', $result->title);
        $this->assertSame('digest', $result->key);
        $this->assertSame(10, (int) $result->organization_id);
    }

    // ─── deleteOrgScoped ────────────────────────────────────────────────────

    public function test_delete_org_scoped_returns_false_when_none_match(): void
    {
        $result = $this->repository->deleteOrgScoped('missing', 99);

        $this->assertFalse($result);
    }

    public function test_delete_org_scoped_deletes_and_returns_true(): void
    {
        $this->insertTemplate([
            'key' => 'farewell',
            'title' => 'Farewell',
            'organization_id' => 3,
        ]);

        $result = $this->repository->deleteOrgScoped('farewell', 3);

        $this->assertTrue($result);
        $this->assertNull($this->repository->findOrgScopedByKey('farewell', 3));
    }

    // ─── listKeysForOrganization ────────────────────────────────────────────

    public function test_list_keys_for_organization_returns_global_and_org_keys(): void
    {
        $this->clearTemplates();

        $this->insertTemplate(['key' => 'global_a', 'title' => 'Global A']);
        $this->insertTemplate(['key' => 'global_b', 'title' => 'Global B']);
        $this->insertTemplate(['key' => 'org_key', 'title' => 'Org Key', 'organization_id' => 5]);
        // Different org — should be excluded
        $this->insertTemplate(['key' => 'other_org', 'title' => 'Other', 'organization_id' => 99]);

        $keys = $this->repository->listKeysForOrganization(5);

        sort($keys);
        $this->assertContains('global_a', $keys);
        $this->assertContains('global_b', $keys);
        $this->assertContains('org_key', $keys);
        $this->assertNotContains('other_org', $keys);
    }

    public function test_list_keys_for_organization_returns_distinct_keys(): void
    {
        $this->clearTemplates();

        // Two global rows with the same key — should only appear once
        $this->insertTemplate(['key' => 'shared', 'title' => 'First']);
        $this->insertTemplate(['key' => 'shared', 'title' => 'Second']);

        $keys = $this->repository->listKeysForOrganization(1);

        $this->assertSame(['shared'], array_values(array_unique($keys)));
    }

    public function test_list_keys_for_organization_excludes_null_keys(): void
    {
        $this->clearTemplates();

        // A template row with no key (ordinary article-backed row) should be excluded.
        // EmailTemplate's global scope filters whereNotNull('key'), so this row
        // won't appear in the query at all.
        $this->insertTemplate(['key' => 'real_key', 'title' => 'Real']);

        $keys = $this->repository->listKeysForOrganization(1);

        $this->assertContains('real_key', $keys);
        $this->assertCount(1, $keys);
    }

    // ─── inherited: create / findAll / findOrFail ───────────────────────────

    public function test_create_persists_template(): void
    {
        $template = $this->repository->create([
            'key' => 'signup',
            'title' => 'Signup Email',
            'organization_id' => null,
        ]);

        $this->assertNotNull($template->id);
        $this->assertSame('Signup Email', $template->title);
        $this->assertSame('signup', $template->key);
    }

    public function test_find_all_returns_all_templates(): void
    {
        $this->clearTemplates();

        $this->insertTemplate(['key' => 'k1', 'title' => 'T1']);
        $this->insertTemplate(['key' => 'k2', 'title' => 'T2']);

        $items = $this->repository->findAll();

        $this->assertCount(2, $items);
    }

    public function test_find_or_fail_returns_model_when_found(): void
    {
        $template = $this->insertTemplate(['key' => 'found', 'title' => 'Found']);

        $result = $this->repository->findOrFail($template->id);

        $this->assertSame($template->id, $result->id);
    }

    public function test_find_or_fail_throws_model_not_found_exception(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $this->repository->findOrFail(999999);
    }
}
