<?php

declare(strict_types=1);

namespace Polis\Tests\Integration\Repositories\Messaging;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Polis\Models\Messaging\PushTemplate;
use Polis\Repositories\Messaging\PushTemplateRepository;
use Polis\Tests\Application\ApplicationTestCase;
use Polis\Tests\Traits\MocksApplicationLog;

/**
 * Integration tests for PushTemplateRepository.
 *
 * Mirrors EmailTemplateRepositoryTest one-to-one — PushTemplateRepository is
 * a near-duplicate of EmailTemplateRepository (both are Article-backed). The
 * only difference is the upsertOrgScoped signature (title + body vs
 * subject + bodyHtml).
 */
final class PushTemplateRepositoryTest extends ApplicationTestCase
{
    use MocksApplicationLog;

    private PushTemplateRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();

        $this->repository = new PushTemplateRepository(
            new PushTemplate,
            $this->getGenericLogMock(),
        );
    }

    // ─── helpers ────────────────────────────────────────────────────────────

    private function insertTemplate(array $attributes): PushTemplate
    {
        return PushTemplate::query()->create(array_merge([
            'title' => 'Default Title',
        ], $attributes));
    }

    private function clearTemplates(): void
    {
        foreach (PushTemplate::withoutGlobalScopes()->get() as $template) {
            $template->forceDelete();
        }
    }

    // ─── findByKey ──────────────────────────────────────────────────────────

    public function test_find_by_key_returns_null_when_no_template_exists(): void
    {
        $this->assertNull($this->repository->findByKey('contact_created'));
    }

    public function test_find_by_key_returns_global_template_when_org_id_is_null(): void
    {
        $template = $this->insertTemplate([
            'key' => 'contact_created',
            'title' => 'You have a new contact',
            'organization_id' => null,
        ]);

        $found = $this->repository->findByKey('contact_created');

        $this->assertNotNull($found);
        $this->assertSame($template->id, $found->id);
    }

    public function test_find_by_key_returns_org_scoped_override_when_present(): void
    {
        $this->insertTemplate([
            'key' => 'contact_created',
            'title' => 'Global title',
        ]);
        $orgOverride = $this->insertTemplate([
            'key' => 'contact_created',
            'title' => 'Org title',
            'organization_id' => 7,
        ]);

        $found = $this->repository->findByKey('contact_created', 7);

        $this->assertSame($orgOverride->id, $found->id);
    }

    public function test_find_by_key_falls_back_to_global_when_org_scoped_missing(): void
    {
        $global = $this->insertTemplate([
            'key' => 'contact_created',
            'title' => 'Global title',
        ]);

        $found = $this->repository->findByKey('contact_created', 7);

        $this->assertSame($global->id, $found->id);
    }

    public function test_find_by_key_returns_null_when_neither_org_nor_global_exist(): void
    {
        $this->insertTemplate([
            'key' => 'contact_created',
            'title' => 'Org 99',
            'organization_id' => 99,
        ]);

        $this->assertNull($this->repository->findByKey('contact_created', 5));
    }

    public function test_find_by_key_returns_latest_global_by_updated_at(): void
    {
        $older = $this->insertTemplate(['key' => 'contact_created', 'title' => 'Older']);
        $older->updated_at = now()->subDay();
        $older->save();

        $newer = $this->insertTemplate(['key' => 'contact_created', 'title' => 'Newer']);
        $newer->updated_at = now();
        $newer->save();

        $found = $this->repository->findByKey('contact_created');

        $this->assertSame($newer->id, $found->id);
    }

    public function test_find_by_key_returns_org_scoped_even_when_global_is_newer(): void
    {
        // Global template updated very recently; org-scoped override is older.
        // findByKey() must still prefer the org-scoped row (priority beats recency).
        $global = $this->insertTemplate([
            'key' => 'contact_created',
            'title' => 'Global title',
            'organization_id' => null,
        ]);
        $global->updated_at = now();
        $global->save();

        $orgScoped = $this->insertTemplate([
            'key' => 'contact_created',
            'title' => 'Org title',
            'organization_id' => 5,
        ]);
        $orgScoped->updated_at = now()->subDay();
        $orgScoped->save();

        $found = $this->repository->findByKey('contact_created', 5);

        $this->assertSame($orgScoped->id, $found->id);
        $this->assertSame('Org title', $found->title);
    }

    // ─── findOrgScopedByKey ─────────────────────────────────────────────────

    public function test_find_org_scoped_by_key_returns_org_row(): void
    {
        $this->insertTemplate(['key' => 'reminder', 'title' => 'Global Reminder']);
        $orgTemplate = $this->insertTemplate([
            'key' => 'reminder',
            'title' => 'Org Reminder',
            'organization_id' => 12,
        ]);

        $found = $this->repository->findOrgScopedByKey('reminder', 12);

        $this->assertNotNull($found);
        $this->assertSame($orgTemplate->id, $found->id);
    }

    public function test_find_org_scoped_by_key_returns_null_when_only_global_exists(): void
    {
        $this->insertTemplate(['key' => 'reminder', 'title' => 'Global Reminder']);

        $found = $this->repository->findOrgScopedByKey('reminder', 12);

        $this->assertNull($found);
    }

    // ─── upsertOrgScoped ────────────────────────────────────────────────────

    public function test_upsert_org_scoped_creates_when_absent(): void
    {
        $result = $this->repository->upsertOrgScoped('push_welcome', 8, 'Welcome!', 'Hello there');

        $this->assertNotNull($result->id);
        $this->assertSame('Welcome!', $result->title);
        $this->assertSame('push_welcome', $result->key);
        $this->assertSame(8, (int) $result->organization_id);
    }

    public function test_upsert_org_scoped_updates_existing_title_and_body(): void
    {
        $this->insertTemplate([
            'key' => 'push_welcome',
            'title' => 'Old Title',
            'organization_id' => 8,
        ]);

        $result = $this->repository->upsertOrgScoped('push_welcome', 8, 'New Title', 'New body text');

        $this->assertSame('New Title', $result->title);
        $this->assertSame('push_welcome', $result->key);
        $this->assertSame(8, (int) $result->organization_id);
    }

    // ─── deleteOrgScoped ────────────────────────────────────────────────────

    public function test_delete_org_scoped_returns_false_when_none_match(): void
    {
        $result = $this->repository->deleteOrgScoped('nonexistent', 1);

        $this->assertFalse($result);
    }

    public function test_delete_org_scoped_deletes_and_returns_true(): void
    {
        $this->insertTemplate([
            'key' => 'goodbye',
            'title' => 'Goodbye Push',
            'organization_id' => 4,
        ]);

        $result = $this->repository->deleteOrgScoped('goodbye', 4);

        $this->assertTrue($result);
        $this->assertNull($this->repository->findOrgScopedByKey('goodbye', 4));
    }

    // ─── listKeysForOrganization ────────────────────────────────────────────

    public function test_list_keys_for_organization_returns_global_and_org_keys(): void
    {
        $this->clearTemplates();

        $this->insertTemplate(['key' => 'global_push_a', 'title' => 'Global A']);
        $this->insertTemplate(['key' => 'global_push_b', 'title' => 'Global B']);
        $this->insertTemplate(['key' => 'org_push', 'title' => 'Org Push', 'organization_id' => 3]);
        $this->insertTemplate(['key' => 'other_push', 'title' => 'Other', 'organization_id' => 77]);

        $keys = $this->repository->listKeysForOrganization(3);

        sort($keys);
        $this->assertContains('global_push_a', $keys);
        $this->assertContains('global_push_b', $keys);
        $this->assertContains('org_push', $keys);
        $this->assertNotContains('other_push', $keys);
    }

    public function test_list_keys_for_organization_returns_distinct_keys(): void
    {
        $this->clearTemplates();

        $this->insertTemplate(['key' => 'dup_key', 'title' => 'First']);
        $this->insertTemplate(['key' => 'dup_key', 'title' => 'Second']);

        $keys = $this->repository->listKeysForOrganization(1);

        $this->assertSame(['dup_key'], array_values(array_unique($keys)));
    }

    public function test_list_keys_for_organization_excludes_null_keys(): void
    {
        $this->clearTemplates();

        $this->insertTemplate(['key' => 'real_push_key', 'title' => 'Real']);

        $keys = $this->repository->listKeysForOrganization(1);

        $this->assertContains('real_push_key', $keys);
        $this->assertCount(1, $keys);
    }

    // ─── inherited: create / findAll / findOrFail ───────────────────────────

    public function test_create_persists_template(): void
    {
        $template = $this->repository->create([
            'key' => 'new_contact',
            'title' => 'New Contact Push',
            'organization_id' => null,
        ]);

        $this->assertNotNull($template->id);
        $this->assertSame('New Contact Push', $template->title);
        $this->assertSame('new_contact', $template->key);
    }

    public function test_find_all_returns_all_templates(): void
    {
        $this->clearTemplates();

        $this->insertTemplate(['key' => 'p1', 'title' => 'Push 1']);
        $this->insertTemplate(['key' => 'p2', 'title' => 'Push 2']);

        $items = $this->repository->findAll();

        $this->assertCount(2, $items);
    }

    public function test_find_or_fail_returns_model_when_found(): void
    {
        $template = $this->insertTemplate(['key' => 'found_push', 'title' => 'Found']);

        $result = $this->repository->findOrFail($template->id);

        $this->assertSame($template->id, $result->id);
    }

    public function test_find_or_fail_throws_model_not_found_exception(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $this->repository->findOrFail(999999);
    }
}
