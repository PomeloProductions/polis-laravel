<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Repositories\Messaging;

use Polis\Models\Messaging\PushTemplate;
use Polis\Repositories\Messaging\PushTemplateRepository;
use Polis\Tests\Fixtures\Repository\RepositoryTestCase;

/**
 * Coverage for PushTemplateRepository's multi-tenant findByKey lookup.
 *
 * Mirrors EmailTemplateRepositoryTest one-to-one — PushTemplateRepository is
 * a near-duplicate of EmailTemplateRepository (both inherit from Article via
 * the templates Article-backed storage pattern). Tests here lock in the same
 * SQL-level guarantees so a regression in one doesn't slip through the
 * other.
 */
final class PushTemplateRepositoryTest extends RepositoryTestCase
{
    private PushTemplateRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new PushTemplateRepository(
            new PushTemplate,
            $this->getGenericLogMock(),
        );
    }

    private function insertTemplate(array $attributes): PushTemplate
    {
        return PushTemplate::query()->create($attributes);
    }

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

    public function test_find_by_key_returns_null_when_no_match_at_all(): void
    {
        $this->insertTemplate([
            'key' => 'something_else',
            'title' => 'Other',
            'organization_id' => 99,
        ]);

        $this->assertNull($this->repository->findByKey('contact_created', 5));
    }

    public function test_find_by_key_returns_latest_by_updated_at(): void
    {
        $older = $this->insertTemplate([
            'key' => 'contact_created',
            'title' => 'Older',
        ]);
        $older->updated_at = now()->subDay();
        $older->save();

        $newer = $this->insertTemplate([
            'key' => 'contact_created',
            'title' => 'Newer',
        ]);
        $newer->updated_at = now();
        $newer->save();

        $found = $this->repository->findByKey('contact_created');

        $this->assertSame($newer->id, $found->id);
    }
}
