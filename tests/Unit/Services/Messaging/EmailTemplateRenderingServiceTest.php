<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Services\Messaging;

use Mockery;
use Polis\Contracts\Messaging\EmailTemplateContract;
use Polis\Contracts\Repositories\Messaging\EmailTemplateRepositoryContract;
use Polis\Exceptions\Messaging\TemplateNotFoundException;
use Polis\Mail\RenderedEmail;
use Polis\Services\Messaging\EmailTemplateRenderingService;
use Polis\Tests\TestCase;

/**
 * Class EmailTemplateRenderingServiceTest
 *
 * Verifies the multi-tenant template lookup hierarchy, default fallback,
 * interpolation rules, and HTML sanitization. Does not exercise the DB —
 * the EmailTemplateRepository is mocked so this test runs in the Unit suite
 * without an Eloquent connection.
 */
final class EmailTemplateRenderingServiceTest extends TestCase
{
    private const DEFAULTS = [
        'welcome' => [
            'subject' => 'Welcome to {{ app.name }}!',
            'body_html' => '<p>Hi {{ user.first_name }},</p>',
        ],
        'no_vars' => [
            'subject' => 'Hello world',
            'body_html' => '<p>Plain</p>',
        ],
    ];

    private function makeServiceWithMockRepo(?EmailTemplateContract $orgResult, ?EmailTemplateContract $globalResult, ?int $orgId, string $key): EmailTemplateRenderingService
    {
        $repo = Mockery::mock(EmailTemplateRepositoryContract::class);
        // The service makes a single findByKey call; the repo internally
        // applies the org->global fallback. So we simulate that here by
        // returning the appropriate result based on the orgId passed.
        $repo->shouldReceive('findByKey')
            ->with($key, $orgId)
            ->andReturn($orgResult ?? $globalResult);

        return new EmailTemplateRenderingService($repo, self::DEFAULTS);
    }

    public function test_renders_with_global_template_when_no_org_scoped_row(): void
    {
        $global = $this->makeEmailTemplate('Global subject', '<p>Global body</p>');
        $service = $this->makeServiceWithMockRepo(null, $global, null, 'welcome');

        $rendered = $service->render('welcome', []);

        $this->assertInstanceOf(RenderedEmail::class, $rendered);
        $this->assertSame('Global subject', $rendered->subject);
        $this->assertSame('<p>Global body</p>', $rendered->bodyHtml);
    }

    public function test_renders_with_org_scoped_template_overriding_global(): void
    {
        $orgScoped = $this->makeEmailTemplate('Org subject', '<p>Org body</p>');
        $service = $this->makeServiceWithMockRepo($orgScoped, null, 42, 'welcome');

        $rendered = $service->render('welcome', [], 42);

        $this->assertSame('Org subject', $rendered->subject);
        $this->assertSame('<p>Org body</p>', $rendered->bodyHtml);
    }

    public function test_falls_back_to_default_when_no_db_row_exists(): void
    {
        $service = $this->makeServiceWithMockRepo(null, null, null, 'welcome');

        $rendered = $service->render('welcome', [
            'user' => ['first_name' => 'Ada'],
            'app' => ['name' => 'Athenia'],
        ]);

        $this->assertSame('Welcome to Athenia!', $rendered->subject);
        $this->assertSame('<p>Hi Ada,</p>', $rendered->bodyHtml);
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
        $service = $this->makeServiceWithMockRepo(null, null, null, 'no_vars');

        // Inject a custom template via a fresh repo just for this case
        $repo = Mockery::mock(EmailTemplateRepositoryContract::class);
        $repo->shouldReceive('findByKey')
            ->with('nested', null)
            ->andReturn($this->makeEmailTemplate(
                'Hi {{ user.profile.display_name }}',
                '<p>Org: {{ user.organization.name }} ({{ user.organization.id }})</p>',
            ));
        $svc = new EmailTemplateRenderingService($repo, []);

        $rendered = $svc->render('nested', [
            'user' => [
                'profile' => ['display_name' => 'Grace H.'],
                'organization' => ['name' => 'Bell Labs', 'id' => 7],
            ],
        ]);

        $this->assertSame('Hi Grace H.', $rendered->subject);
        $this->assertSame('<p>Org: Bell Labs (7)</p>', $rendered->bodyHtml);
    }

    public function test_variable_interpolation_returns_empty_string_for_missing_variables(): void
    {
        $repo = Mockery::mock(EmailTemplateRepositoryContract::class);
        $repo->shouldReceive('findByKey')
            ->with('missing', null)
            ->andReturn($this->makeEmailTemplate('Hi {{ user.name }}!', '<p>{{ missing.deeply.nested }}</p>'));
        $svc = new EmailTemplateRenderingService($repo, []);

        $rendered = $svc->render('missing', []);

        $this->assertSame('Hi !', $rendered->subject);
        $this->assertSame('<p></p>', $rendered->bodyHtml);
    }

    public function test_html_sanitization_strips_script_tags(): void
    {
        $repo = Mockery::mock(EmailTemplateRepositoryContract::class);
        $repo->shouldReceive('findByKey')
            ->with('xss', null)
            ->andReturn($this->makeEmailTemplate('OK', '<p>Hi</p><script>alert(1)</script><p>End</p>'));
        $svc = new EmailTemplateRenderingService($repo, []);

        $rendered = $svc->render('xss', []);

        $this->assertStringNotContainsString('<script>', $rendered->bodyHtml);
        $this->assertStringNotContainsString('alert(1)', $rendered->bodyHtml);
        $this->assertStringContainsString('<p>Hi</p>', $rendered->bodyHtml);
        $this->assertStringContainsString('<p>End</p>', $rendered->bodyHtml);
    }

    public function test_html_sanitization_strips_event_handlers_and_js_urls(): void
    {
        $repo = Mockery::mock(EmailTemplateRepositoryContract::class);
        $repo->shouldReceive('findByKey')
            ->with('xss2', null)
            ->andReturn($this->makeEmailTemplate(
                'OK',
                '<a href="javascript:alert(1)" onclick="bad()">click</a>',
            ));
        $svc = new EmailTemplateRenderingService($repo, []);

        $rendered = $svc->render('xss2', []);

        $this->assertStringNotContainsString('onclick', $rendered->bodyHtml);
        $this->assertStringNotContainsString('javascript:', $rendered->bodyHtml);
    }

    public function test_variable_values_are_html_escaped_to_prevent_injection(): void
    {
        $repo = Mockery::mock(EmailTemplateRepositoryContract::class);
        $repo->shouldReceive('findByKey')
            ->with('escape', null)
            ->andReturn($this->makeEmailTemplate('Hello {{ name }}', '<p>Hi {{ name }}</p>'));
        $svc = new EmailTemplateRenderingService($repo, []);

        $rendered = $svc->render('escape', ['name' => '<script>alert(1)</script>']);

        // Subject is not body-escaped (callers should not place untrusted
        // content in subjects, but they're also not displayed as HTML).
        // Body interpolation escapes.
        $this->assertStringNotContainsString('<script>alert(1)</script>', $rendered->bodyHtml);
        $this->assertStringContainsString('&lt;script&gt;', $rendered->bodyHtml);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function makeEmailTemplate(string $subject, string $bodyHtml): EmailTemplateContract
    {
        // A minimal EmailTemplateContract implementation that bypasses the
        // real Eloquent EmailTemplate model. The real model extends
        // Polis\Models\Wiki\Article which uses the consumer-app
        // AdminUI\Laravel\EloquentJoin trait — not available in this
        // package's standalone test environment, so we satisfy the
        // contract directly here.
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
