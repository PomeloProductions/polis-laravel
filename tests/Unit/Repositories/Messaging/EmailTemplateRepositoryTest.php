<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Repositories\Messaging;

use Polis\Models\Messaging\EmailTemplate;
use Polis\Repositories\Messaging\EmailTemplateRepository;
use Polis\Tests\Fixtures\Repository\RepositoryTestCase;

/**
 * Coverage for EmailTemplateRepository's multi-tenant findByKey lookup.
 *
 * Backed by an in-memory sqlite database with the minimal `articles`
 * schema created in tests/Fixtures/database/migrations/2026_06_05_000002_*.
 * That lets us exercise the real SQL: the `organizationId === null` branch,
 * the org-scoped-then-fallback branch, the latest('updated_at') ordering,
 * and the "no match" null return.
 */
final class EmailTemplateRepositoryTest extends RepositoryTestCase
{
    private EmailTemplateRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new EmailTemplateRepository(
            new EmailTemplate,
            $this->getGenericLogMock(),
        );
    }

    private function insertTemplate(array $attributes): EmailTemplate
    {
        return EmailTemplate::query()->create($attributes);
    }

    public function test_find_by_key_returns_null_when_no_template_exists(): void
    {
        $this->assertNull($this->repository->findByKey('missing'));
    }

    public function test_find_by_key_returns_global_template_when_org_id_is_null(): void
    {
        $template = $this->insertTemplate([
            'key' => 'welcome',
            'title' => 'Welcome to the App',
            'organization_id' => null,
        ]);

        $found = $this->repository->findByKey('welcome');

        $this->assertNotNull($found);
        $this->assertSame($template->id, $found->id);
        $this->assertSame('Welcome to the App', $found->title);
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
            'title' => 'Org-specific Welcome',
            'organization_id' => 42,
        ]);

        $found = $this->repository->findByKey('welcome', 42);

        $this->assertSame($orgOverride->id, $found->id);
        $this->assertSame('Org-specific Welcome', $found->title);
        $this->assertNotEquals($global->id, $found->id);
    }

    public function test_find_by_key_falls_back_to_global_when_org_scoped_missing(): void
    {
        $global = $this->insertTemplate([
            'key' => 'welcome',
            'title' => 'Global Welcome',
            'organization_id' => null,
        ]);
        // org 42 has no override of its own
        $this->insertTemplate([
            'key' => 'welcome',
            'title' => 'Org 99 Welcome',
            'organization_id' => 99,
        ]);

        $found = $this->repository->findByKey('welcome', 42);

        $this->assertSame($global->id, $found->id);
    }

    public function test_find_by_key_returns_null_when_org_scoped_and_no_global_exist(): void
    {
        $this->insertTemplate([
            'key' => 'welcome',
            'title' => 'Org 99 Welcome',
            'organization_id' => 99,
        ]);

        $this->assertNull($this->repository->findByKey('welcome', 42));
    }

    public function test_find_by_key_returns_latest_by_updated_at(): void
    {
        $older = $this->insertTemplate([
            'key' => 'welcome',
            'title' => 'Older Welcome',
        ]);
        $older->updated_at = now()->subDay();
        $older->save();

        $newer = $this->insertTemplate([
            'key' => 'welcome',
            'title' => 'Newer Welcome',
        ]);
        $newer->updated_at = now();
        $newer->save();

        $found = $this->repository->findByKey('welcome');

        $this->assertSame($newer->id, $found->id);
    }

    public function test_find_by_key_returns_latest_org_scoped_by_updated_at(): void
    {
        $older = $this->insertTemplate([
            'key' => 'welcome',
            'title' => 'Older Org',
            'organization_id' => 5,
        ]);
        $older->updated_at = now()->subDays(2);
        $older->save();

        $newer = $this->insertTemplate([
            'key' => 'welcome',
            'title' => 'Newer Org',
            'organization_id' => 5,
        ]);
        $newer->updated_at = now();
        $newer->save();

        $found = $this->repository->findByKey('welcome', 5);

        $this->assertSame($newer->id, $found->id);
    }
}
