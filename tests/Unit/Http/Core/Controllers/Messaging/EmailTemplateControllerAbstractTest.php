<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Http\Core\Controllers\Messaging;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Mockery;
use Mockery\MockInterface;
use Polis\Contracts\Messaging\EmailTemplateContract;
use Polis\Contracts\Repositories\Messaging\EmailTemplateRepositoryContract;
use Polis\Mail\DefaultEmailTemplates;
use Polis\Tests\Mocks\EmailTemplateController;
use Polis\Tests\TestCase;

/**
 * Unit-level coverage for EmailTemplateControllerAbstract.
 *
 * Mocks the EmailTemplateRepositoryContract and the consumer-app
 * Organization model so the controller can be exercised standalone (no
 * DB, no consumer-app autoload). Verifies the lookup hierarchy + payload
 * shape promised in the controller's class docblock, plus the upsert +
 * revert behavior wired through the repository.
 */
final class EmailTemplateControllerAbstractTest extends TestCase
{
    private const ORG_ID = 42;

    public function test_index_returns_known_keys_with_resolved_source(): void
    {
        $repo = Mockery::mock(EmailTemplateRepositoryContract::class);
        $organization = $this->makeOrganization(self::ORG_ID);

        // listKeysForOrganization returns a key only present in DB (no
        // default for it) — controller should still include it.
        $repo->shouldReceive('listKeysForOrganization')
            ->with(self::ORG_ID)
            ->andReturn(['org_only_key']);

        // For 'org_only_key' there is an org-scoped row (source=org).
        $orgRow = $this->makeTemplate('Org subject', '<p>Org body</p>');
        $repo->shouldReceive('findOrgScopedByKey')
            ->with('org_only_key', self::ORG_ID)
            ->andReturn($orgRow);

        // For every default key, neither org row nor global row exists,
        // so source=default and resolved == default.
        foreach (array_keys(DefaultEmailTemplates::TEMPLATES) as $defaultKey) {
            $repo->shouldReceive('findOrgScopedByKey')
                ->with($defaultKey, self::ORG_ID)
                ->andReturn(null);
            $repo->shouldReceive('findByKey')
                ->with($defaultKey, self::ORG_ID)
                ->andReturn(null);
        }

        $controller = new EmailTemplateController($repo);
        $request = $this->makeIndexRequest();

        $response = $controller->index($request, $organization);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $payload = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('data', $payload);

        $byKey = [];
        foreach ($payload['data'] as $entry) {
            $byKey[$entry['key']] = $entry;
        }

        // org_only_key is org-scoped
        $this->assertArrayHasKey('org_only_key', $byKey);
        $this->assertSame('org', $byKey['org_only_key']['source']);
        $this->assertSame('Org subject', $byKey['org_only_key']['subject']);
        $this->assertSame('<p>Org body</p>', $byKey['org_only_key']['body_html']);
        $this->assertSame(self::ORG_ID, $byKey['org_only_key']['organization_id']);
        // No default for this key — defaults are empty strings
        $this->assertSame('', $byKey['org_only_key']['default_subject']);
        $this->assertSame('', $byKey['org_only_key']['default_body_html']);

        // Every in-code default appears with source=default
        foreach (array_keys(DefaultEmailTemplates::TEMPLATES) as $defaultKey) {
            $this->assertArrayHasKey($defaultKey, $byKey, "Missing default key {$defaultKey}");
            $this->assertSame('default', $byKey[$defaultKey]['source']);
            $this->assertNull($byKey[$defaultKey]['organization_id']);
            $this->assertSame(
                DefaultEmailTemplates::TEMPLATES[$defaultKey]['subject'],
                $byKey[$defaultKey]['subject'],
            );
            $this->assertSame(
                DefaultEmailTemplates::TEMPLATES[$defaultKey]['body_html'],
                $byKey[$defaultKey]['body_html'],
            );
        }
    }

    public function test_show_returns_global_source_when_only_global_row_exists(): void
    {
        $repo = Mockery::mock(EmailTemplateRepositoryContract::class);
        $organization = $this->makeOrganization(self::ORG_ID);

        $repo->shouldReceive('findOrgScopedByKey')
            ->with('welcome', self::ORG_ID)
            ->andReturn(null);
        $repo->shouldReceive('findByKey')
            ->with('welcome', self::ORG_ID)
            ->andReturn($this->makeTemplate('Global subject', '<p>Global body</p>'));

        $controller = new EmailTemplateController($repo);
        $response = $controller->show($this->makeViewRequest(), $organization, 'welcome');

        $payload = json_decode($response->getContent(), true);
        $this->assertSame('welcome', $payload['key']);
        $this->assertSame('global', $payload['source']);
        $this->assertNull($payload['organization_id']);
        $this->assertSame('Global subject', $payload['subject']);
        $this->assertSame('<p>Global body</p>', $payload['body_html']);
        // Default copy still surfaced for diff display
        $this->assertSame(
            DefaultEmailTemplates::TEMPLATES['welcome']['subject'],
            $payload['default_subject'],
        );
    }

    public function test_show_returns_default_source_when_no_db_row_exists(): void
    {
        $repo = Mockery::mock(EmailTemplateRepositoryContract::class);
        $organization = $this->makeOrganization(self::ORG_ID);

        $repo->shouldReceive('findOrgScopedByKey')
            ->with('welcome', self::ORG_ID)
            ->andReturn(null);
        $repo->shouldReceive('findByKey')
            ->with('welcome', self::ORG_ID)
            ->andReturn(null);

        $controller = new EmailTemplateController($repo);
        $response = $controller->show($this->makeViewRequest(), $organization, 'welcome');

        $payload = json_decode($response->getContent(), true);
        $this->assertSame('default', $payload['source']);
        $this->assertNull($payload['organization_id']);
        $this->assertSame(
            DefaultEmailTemplates::TEMPLATES['welcome']['subject'],
            $payload['subject'],
        );
    }

    public function test_update_delegates_upsert_to_repository_and_returns_org_source(): void
    {
        $repo = Mockery::mock(EmailTemplateRepositoryContract::class);
        $organization = $this->makeOrganization(self::ORG_ID);

        // Controller delegates the upsert in a single repo call.
        $repo->shouldReceive('upsertOrgScoped')
            ->once()
            ->with('welcome', self::ORG_ID, 'Subject A', '<p>Body A</p>')
            ->andReturn($this->makeTemplate('Subject A', '<p>Body A</p>'));

        // Response rebuild: findOrgScopedByKey now sees the upserted row.
        $repo->shouldReceive('findOrgScopedByKey')
            ->with('welcome', self::ORG_ID)
            ->andReturn($this->makeTemplate('Subject A', '<p>Body A</p>'));

        $controller = new EmailTemplateController($repo);
        $request = $this->makeUpdateRequest(['subject' => 'Subject A', 'body_html' => '<p>Body A</p>']);

        $response = $controller->update($request, $organization, 'welcome');

        $payload = json_decode($response->getContent(), true);
        $this->assertSame('org', $payload['source']);
        $this->assertSame('Subject A', $payload['subject']);
        $this->assertSame('<p>Body A</p>', $payload['body_html']);
        $this->assertSame(self::ORG_ID, $payload['organization_id']);
    }

    public function test_destroy_delegates_to_repository_and_returns_no_content(): void
    {
        $repo = Mockery::mock(EmailTemplateRepositoryContract::class);
        $organization = $this->makeOrganization(self::ORG_ID);

        $repo->shouldReceive('deleteOrgScoped')
            ->once()
            ->with('welcome', self::ORG_ID)
            ->andReturn(true);

        $controller = new EmailTemplateController($repo);
        $response = $controller->destroy($this->makeDeleteRequest(), $organization, 'welcome');

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(204, $response->getStatusCode());
    }

    public function test_destroy_is_idempotent_when_no_org_row_exists(): void
    {
        $repo = Mockery::mock(EmailTemplateRepositoryContract::class);
        $organization = $this->makeOrganization(self::ORG_ID);

        // Repository returns false (no row deleted); controller still 204.
        $repo->shouldReceive('deleteOrgScoped')
            ->once()
            ->with('welcome', self::ORG_ID)
            ->andReturn(false);

        $controller = new EmailTemplateController($repo);
        $response = $controller->destroy($this->makeDeleteRequest(), $organization, 'welcome');

        $this->assertSame(204, $response->getStatusCode());
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function makeOrganization(int $id): MockInterface
    {
        $org = Mockery::mock('App\\Models\\Organization\\Organization');
        $org->id = $id;

        return $org;
    }

    private function makeIndexRequest(): MockInterface
    {
        return Mockery::mock('Polis\\Http\\Core\\Requests\\Messaging\\EmailTemplate\\IndexRequest');
    }

    private function makeViewRequest(): MockInterface
    {
        return Mockery::mock('Polis\\Http\\Core\\Requests\\Messaging\\EmailTemplate\\ViewRequest');
    }

    private function makeUpdateRequest(array $payload): MockInterface
    {
        $request = Mockery::mock('Polis\\Http\\Core\\Requests\\Messaging\\EmailTemplate\\UpdateRequest');
        $request->shouldReceive('input')
            ->with('subject')
            ->andReturn($payload['subject']);
        $request->shouldReceive('input')
            ->with('body_html')
            ->andReturn($payload['body_html']);

        return $request;
    }

    private function makeDeleteRequest(): MockInterface
    {
        return Mockery::mock('Polis\\Http\\Core\\Requests\\Messaging\\EmailTemplate\\DeleteRequest');
    }

    /**
     * A pure EmailTemplateContract (no BaseModelAbstract) — used when the
     * controller only needs to read subject/body for the response payload.
     */
    private function makeTemplate(string $subject, string $bodyHtml): EmailTemplateContract
    {
        return new class($subject, $bodyHtml) implements EmailTemplateContract
        {
            public function __construct(private readonly string $subject, private readonly string $bodyHtml) {}

            public function getSubject(): ?string
            {
                return $this->subject;
            }

            public function getBodyHtml(): ?string
            {
                return $this->bodyHtml;
            }
        };
    }
}
