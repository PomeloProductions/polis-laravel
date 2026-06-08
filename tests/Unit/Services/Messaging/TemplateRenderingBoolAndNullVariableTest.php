<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Services\Messaging;

use Mockery;
use Polis\Contracts\Messaging\EmailTemplateContract;
use Polis\Contracts\Messaging\PushTemplateContract;
use Polis\Contracts\Repositories\Messaging\EmailTemplateRepositoryContract;
use Polis\Contracts\Repositories\Messaging\PushTemplateRepositoryContract;
use Polis\Services\Messaging\EmailTemplateRenderingService;
use Polis\Services\Messaging\PushTemplateRenderingService;
use Polis\Tests\TestCase;

/**
 * Covers the bool and explicit-null branches of the interpolation
 * callbacks in EmailTemplateRenderingService and
 * PushTemplateRenderingService — branches that the existing tests
 * miss (they cover string variables only).
 */
final class TemplateRenderingBoolAndNullVariableTest extends TestCase
{
    public function test_email_template_renders_true_as_1_and_false_as_empty(): void
    {
        $tpl = $this->makeEmailTemplate('{{ flag }}', 'A:{{ flag }} B:{{ off }}');
        $repo = Mockery::mock(EmailTemplateRepositoryContract::class);
        $repo->shouldReceive('findByKey')->andReturn($tpl);
        $svc = new EmailTemplateRenderingService($repo, []);

        $rendered = $svc->render('k', ['flag' => true, 'off' => false]);

        $this->assertSame('1', $rendered->subject);
        // Body is HTML-escaped; '1' stays '1', empty stays empty.
        $this->assertSame('A:1 B:', $rendered->bodyHtml);
    }

    public function test_email_template_renders_null_as_empty_string(): void
    {
        $tpl = $this->makeEmailTemplate('{{ value }}', '[{{ value }}]');
        $repo = Mockery::mock(EmailTemplateRepositoryContract::class);
        $repo->shouldReceive('findByKey')->andReturn($tpl);
        $svc = new EmailTemplateRenderingService($repo, []);

        $rendered = $svc->render('k', ['value' => null]);

        $this->assertSame('', $rendered->subject);
        $this->assertSame('[]', $rendered->bodyHtml);
    }

    public function test_email_template_renders_array_value_as_empty(): void
    {
        // Non-scalar values should resolve to empty string (the
        // is_scalar() guard at line 98).
        $tpl = $this->makeEmailTemplate('S:{{ x }}', 'B:{{ x }}');
        $repo = Mockery::mock(EmailTemplateRepositoryContract::class);
        $repo->shouldReceive('findByKey')->andReturn($tpl);
        $svc = new EmailTemplateRenderingService($repo, []);

        $rendered = $svc->render('k', ['x' => ['nested' => 'data']]);

        $this->assertSame('S:', $rendered->subject);
        $this->assertSame('B:', $rendered->bodyHtml);
    }

    public function test_push_template_renders_true_as_1_and_false_as_empty(): void
    {
        $tpl = $this->makePushTemplate('{{ flag }}', 'A:{{ flag }} B:{{ off }}');
        $repo = Mockery::mock(PushTemplateRepositoryContract::class);
        $repo->shouldReceive('findByKey')->andReturn($tpl);
        $svc = new PushTemplateRenderingService($repo, []);

        $rendered = $svc->render('k', ['flag' => true, 'off' => false]);

        $this->assertSame('1', $rendered->title);
        $this->assertSame('A:1 B:', $rendered->body);
    }

    public function test_push_template_renders_null_as_empty(): void
    {
        $tpl = $this->makePushTemplate('{{ x }}', '[{{ x }}]');
        $repo = Mockery::mock(PushTemplateRepositoryContract::class);
        $repo->shouldReceive('findByKey')->andReturn($tpl);
        $svc = new PushTemplateRenderingService($repo, []);

        $rendered = $svc->render('k', ['x' => null]);

        $this->assertSame('', $rendered->title);
        $this->assertSame('[]', $rendered->body);
    }

    public function test_push_template_renders_non_scalar_as_empty(): void
    {
        $tpl = $this->makePushTemplate('T:{{ x }}', 'B:{{ x }}');
        $repo = Mockery::mock(PushTemplateRepositoryContract::class);
        $repo->shouldReceive('findByKey')->andReturn($tpl);
        $svc = new PushTemplateRenderingService($repo, []);

        $rendered = $svc->render('k', ['x' => (object) ['a' => 1]]);

        $this->assertSame('T:', $rendered->title);
        $this->assertSame('B:', $rendered->body);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function makeEmailTemplate(string $subject, string $bodyHtml): EmailTemplateContract
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

    private function makePushTemplate(string $title, string $body): PushTemplateContract
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
