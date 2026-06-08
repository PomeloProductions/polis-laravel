<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Http\Core\Controllers\Messaging;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Mockery;
use Mockery\MockInterface;
use Polis\Contracts\Messaging\PushTemplateContract;
use Polis\Contracts\Repositories\Messaging\PushTemplateRepositoryContract;
use Polis\Push\DefaultPushTemplates;
use Polis\Tests\Mocks\PushTemplateController;
use Polis\Tests\TestCase;

/**
 * Unit-level coverage for PushTemplateControllerAbstract. Mirrors the
 * EmailTemplateControllerAbstractTest one-to-one; differences are the
 * payload field names (title/body vs subject/body_html).
 */
final class PushTemplateControllerAbstractTest extends TestCase
{
    private const ORG_ID = 99;

    public function test_index_returns_known_keys_with_resolved_source(): void
    {
        $repo = Mockery::mock(PushTemplateRepositoryContract::class);
        $organization = $this->makeOrganization(self::ORG_ID);

        $repo->shouldReceive('listKeysForOrganization')
            ->with(self::ORG_ID)
            ->andReturn(['custom_push_key']);

        $orgRow = $this->makeTemplate('Org title', 'Org body');
        $repo->shouldReceive('findOrgScopedByKey')
            ->with('custom_push_key', self::ORG_ID)
            ->andReturn($orgRow);

        foreach (array_keys(DefaultPushTemplates::TEMPLATES) as $defaultKey) {
            $repo->shouldReceive('findOrgScopedByKey')
                ->with($defaultKey, self::ORG_ID)
                ->andReturn(null);
            $repo->shouldReceive('findByKey')
                ->with($defaultKey, self::ORG_ID)
                ->andReturn(null);
        }

        $controller = new PushTemplateController($repo);
        $response = $controller->index($this->makeIndexRequest(), $organization);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $payload = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('data', $payload);

        $byKey = [];
        foreach ($payload['data'] as $entry) {
            $byKey[$entry['key']] = $entry;
        }

        $this->assertArrayHasKey('custom_push_key', $byKey);
        $this->assertSame('org', $byKey['custom_push_key']['source']);
        $this->assertSame('Org title', $byKey['custom_push_key']['title']);
        $this->assertSame('Org body', $byKey['custom_push_key']['body']);
        $this->assertSame(self::ORG_ID, $byKey['custom_push_key']['organization_id']);
        $this->assertSame('', $byKey['custom_push_key']['default_title']);

        foreach (array_keys(DefaultPushTemplates::TEMPLATES) as $defaultKey) {
            $this->assertArrayHasKey($defaultKey, $byKey);
            $this->assertSame('default', $byKey[$defaultKey]['source']);
            $this->assertNull($byKey[$defaultKey]['organization_id']);
            $this->assertSame(
                DefaultPushTemplates::TEMPLATES[$defaultKey]['title'],
                $byKey[$defaultKey]['title'],
            );
            $this->assertSame(
                DefaultPushTemplates::TEMPLATES[$defaultKey]['body'],
                $byKey[$defaultKey]['body'],
            );
        }
    }

    public function test_show_returns_global_source_when_only_global_row_exists(): void
    {
        $repo = Mockery::mock(PushTemplateRepositoryContract::class);
        $organization = $this->makeOrganization(self::ORG_ID);

        $repo->shouldReceive('findOrgScopedByKey')
            ->with('contact_created', self::ORG_ID)
            ->andReturn(null);
        $repo->shouldReceive('findByKey')
            ->with('contact_created', self::ORG_ID)
            ->andReturn($this->makeTemplate('Global title', 'Global body'));

        $controller = new PushTemplateController($repo);
        $response = $controller->show($this->makeViewRequest(), $organization, 'contact_created');

        $payload = json_decode($response->getContent(), true);
        $this->assertSame('global', $payload['source']);
        $this->assertNull($payload['organization_id']);
        $this->assertSame('Global title', $payload['title']);
        $this->assertSame('Global body', $payload['body']);
    }

    public function test_show_returns_default_source_when_no_db_row_exists(): void
    {
        $repo = Mockery::mock(PushTemplateRepositoryContract::class);
        $organization = $this->makeOrganization(self::ORG_ID);

        $repo->shouldReceive('findOrgScopedByKey')
            ->with('contact_created', self::ORG_ID)
            ->andReturn(null);
        $repo->shouldReceive('findByKey')
            ->with('contact_created', self::ORG_ID)
            ->andReturn(null);

        $controller = new PushTemplateController($repo);
        $response = $controller->show($this->makeViewRequest(), $organization, 'contact_created');

        $payload = json_decode($response->getContent(), true);
        $this->assertSame('default', $payload['source']);
        $this->assertSame(
            DefaultPushTemplates::TEMPLATES['contact_created']['title'],
            $payload['title'],
        );
    }

    public function test_update_delegates_upsert_to_repository_and_returns_org_source(): void
    {
        $repo = Mockery::mock(PushTemplateRepositoryContract::class);
        $organization = $this->makeOrganization(self::ORG_ID);

        $repo->shouldReceive('upsertOrgScoped')
            ->once()
            ->with('contact_created', self::ORG_ID, 'New title', 'New body')
            ->andReturn($this->makeTemplate('New title', 'New body'));

        $repo->shouldReceive('findOrgScopedByKey')
            ->with('contact_created', self::ORG_ID)
            ->andReturn($this->makeTemplate('New title', 'New body'));

        $controller = new PushTemplateController($repo);
        $request = $this->makeUpdateRequest(['title' => 'New title', 'body' => 'New body']);

        $response = $controller->update($request, $organization, 'contact_created');

        $payload = json_decode($response->getContent(), true);
        $this->assertSame('org', $payload['source']);
        $this->assertSame('New title', $payload['title']);
        $this->assertSame('New body', $payload['body']);
        $this->assertSame(self::ORG_ID, $payload['organization_id']);
    }

    public function test_destroy_delegates_to_repository_and_returns_no_content(): void
    {
        $repo = Mockery::mock(PushTemplateRepositoryContract::class);
        $organization = $this->makeOrganization(self::ORG_ID);

        $repo->shouldReceive('deleteOrgScoped')
            ->once()
            ->with('contact_created', self::ORG_ID)
            ->andReturn(true);

        $controller = new PushTemplateController($repo);
        $response = $controller->destroy($this->makeDeleteRequest(), $organization, 'contact_created');

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(204, $response->getStatusCode());
    }

    public function test_destroy_is_idempotent_when_no_org_row_exists(): void
    {
        $repo = Mockery::mock(PushTemplateRepositoryContract::class);
        $organization = $this->makeOrganization(self::ORG_ID);

        $repo->shouldReceive('deleteOrgScoped')
            ->once()
            ->with('contact_created', self::ORG_ID)
            ->andReturn(false);

        $controller = new PushTemplateController($repo);
        $response = $controller->destroy($this->makeDeleteRequest(), $organization, 'contact_created');

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
        return Mockery::mock('App\\Http\\Core\\Requests\\Messaging\\PushTemplate\\IndexRequest');
    }

    private function makeViewRequest(): MockInterface
    {
        return Mockery::mock('App\\Http\\Core\\Requests\\Messaging\\PushTemplate\\ViewRequest');
    }

    private function makeUpdateRequest(array $payload): MockInterface
    {
        $request = Mockery::mock('App\\Http\\Core\\Requests\\Messaging\\PushTemplate\\UpdateRequest');
        $request->shouldReceive('input')
            ->with('title')
            ->andReturn($payload['title']);
        $request->shouldReceive('input')
            ->with('body')
            ->andReturn($payload['body']);

        return $request;
    }

    private function makeDeleteRequest(): MockInterface
    {
        return Mockery::mock('App\\Http\\Core\\Requests\\Messaging\\PushTemplate\\DeleteRequest');
    }

    private function makeTemplate(string $title, string $body): PushTemplateContract
    {
        return new class($title, $body) implements PushTemplateContract
        {
            public function __construct(private readonly string $title, private readonly string $body) {}

            public function getTitle(): ?string
            {
                return $this->title;
            }

            public function getBody(): ?string
            {
                return $this->body;
            }
        };
    }
}
