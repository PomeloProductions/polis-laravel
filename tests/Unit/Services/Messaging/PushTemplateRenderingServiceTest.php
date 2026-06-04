<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Services\Messaging;

use Mockery;
use Polis\Contracts\Messaging\PushTemplateContract;
use Polis\Contracts\Repositories\Messaging\PushTemplateRepositoryContract;
use Polis\Exceptions\Messaging\TemplateNotFoundException;
use Polis\Push\RenderedPushNotification;
use Polis\Services\Messaging\PushTemplateRenderingService;
use Polis\Tests\TestCase;

/**
 * Class PushTemplateRenderingServiceTest
 *
 * Verifies the multi-tenant template lookup hierarchy, default fallback,
 * and interpolation rules for push notifications. Mirrors
 * EmailTemplateRenderingServiceTest but without the HTML-sanitization
 * coverage — push notification bodies are plain text and the service
 * deliberately does not run a sanitizer pass.
 *
 * Does not exercise the DB — the PushTemplateRepository is mocked so
 * this test runs in the Unit suite without an Eloquent connection.
 */
final class PushTemplateRenderingServiceTest extends TestCase
{
    private const DEFAULTS = [
        'contact_created' => [
            'title' => 'New Contact Request!',
            'body' => '{{ contact.initiator.first_name }} wants to connect with you!',
        ],
        'no_vars' => [
            'title' => 'Hello',
            'body' => 'Plain body',
        ],
    ];

    private function makeServiceWithMockRepo(?PushTemplateContract $orgResult, ?PushTemplateContract $globalResult, ?int $orgId, string $key): PushTemplateRenderingService
    {
        $repo = Mockery::mock(PushTemplateRepositoryContract::class);
        // The service makes a single findByKey call; the repo internally
        // applies the org->global fallback. So we simulate that here by
        // returning the appropriate result based on the orgId passed.
        $repo->shouldReceive('findByKey')
            ->with($key, $orgId)
            ->andReturn($orgResult ?? $globalResult);

        return new PushTemplateRenderingService($repo, self::DEFAULTS);
    }

    public function test_renders_with_global_template_when_no_org_scoped_row(): void
    {
        $global = $this->makePushTemplate('Global title', 'Global body');
        $service = $this->makeServiceWithMockRepo(null, $global, null, 'contact_created');

        $rendered = $service->render('contact_created', []);

        $this->assertInstanceOf(RenderedPushNotification::class, $rendered);
        $this->assertSame('Global title', $rendered->title);
        $this->assertSame('Global body', $rendered->body);
    }

    public function test_renders_with_org_scoped_template_overriding_global(): void
    {
        $orgScoped = $this->makePushTemplate('Org title', 'Org body');
        $service = $this->makeServiceWithMockRepo($orgScoped, null, 42, 'contact_created');

        $rendered = $service->render('contact_created', [], 42);

        $this->assertSame('Org title', $rendered->title);
        $this->assertSame('Org body', $rendered->body);
    }

    public function test_falls_back_to_default_when_no_db_row_exists(): void
    {
        $service = $this->makeServiceWithMockRepo(null, null, null, 'contact_created');

        $rendered = $service->render('contact_created', [
            'contact' => ['initiator' => ['first_name' => 'Ada']],
        ]);

        $this->assertSame('New Contact Request!', $rendered->title);
        $this->assertSame('Ada wants to connect with you!', $rendered->body);
    }

    public function test_throws_when_no_default_and_no_db_row(): void
    {
        $service = $this->makeServiceWithMockRepo(null, null, null, 'nonexistent_key');

        $this->expectException(TemplateNotFoundException::class);
        $this->expectExceptionMessageMatches('/nonexistent_key/');

        $service->render('nonexistent_key', []);
    }

    public function test_variable_interpolation_handles_nested_paths(): void
    {
        $repo = Mockery::mock(PushTemplateRepositoryContract::class);
        $repo->shouldReceive('findByKey')
            ->with('nested', null)
            ->andReturn($this->makePushTemplate(
                'Hi {{ user.profile.display_name }}',
                'Org: {{ user.organization.name }} ({{ user.organization.id }})',
            ));
        $svc = new PushTemplateRenderingService($repo, []);

        $rendered = $svc->render('nested', [
            'user' => [
                'profile' => ['display_name' => 'Grace H.'],
                'organization' => ['name' => 'Bell Labs', 'id' => 7],
            ],
        ]);

        $this->assertSame('Hi Grace H.', $rendered->title);
        $this->assertSame('Org: Bell Labs (7)', $rendered->body);
    }

    public function test_variable_interpolation_returns_empty_string_for_missing_variables(): void
    {
        $repo = Mockery::mock(PushTemplateRepositoryContract::class);
        $repo->shouldReceive('findByKey')
            ->with('missing', null)
            ->andReturn($this->makePushTemplate('Hi {{ user.name }}!', '{{ missing.deeply.nested }}'));
        $svc = new PushTemplateRenderingService($repo, []);

        $rendered = $svc->render('missing', []);

        $this->assertSame('Hi !', $rendered->title);
        $this->assertSame('', $rendered->body);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function makePushTemplate(string $title, string $body): PushTemplateContract
    {
        // A minimal PushTemplateContract implementation that bypasses the
        // real Eloquent PushTemplate model. The real model extends
        // Polis\Models\Wiki\Article which uses the consumer-app
        // AdminUI\Laravel\EloquentJoin trait — not available in this
        // package's standalone test environment, so we satisfy the
        // contract directly here.
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
