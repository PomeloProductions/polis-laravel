<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Mail;

use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Polis\Contracts\Repositories\Messaging\EmailTemplateRepositoryContract;
use Polis\Mail\DefaultEmailTemplates;
use Polis\Services\Messaging\EmailTemplateRenderingService;
use Polis\Tests\TestCase;

/**
 * Pin the structure of in-code email-template defaults. Changes here are
 * intentional template content shifts — keep the shape stable so consumer
 * code reading TEMPLATES[$key] can rely on `subject` and `body_html`.
 *
 * Also exercises each default template through EmailTemplateRenderingService
 * with representative sample variables to guarantee that the in-code copy
 * still renders to a non-empty string (i.e. that placeholders are syntactically
 * valid and reference variables the call sites actually provide).
 */
final class DefaultEmailTemplatesTest extends TestCase
{
    public function test_templates_constant_exposes_expected_keys(): void
    {
        $keys = array_keys(DefaultEmailTemplates::TEMPLATES);

        $this->assertContains('welcome', $keys);
        $this->assertContains('organization_manager_added', $keys);
        $this->assertContains('renewal_reminder', $keys);
        $this->assertContains('renewal_receipt', $keys);
        $this->assertContains('renewal_failure', $keys);
        $this->assertContains('membership_expired', $keys);
        $this->assertCount(6, $keys);
    }

    public function test_every_template_has_subject_and_body_html_keys(): void
    {
        foreach (DefaultEmailTemplates::TEMPLATES as $key => $template) {
            $this->assertArrayHasKey('subject', $template, "Template '{$key}' missing subject");
            $this->assertArrayHasKey('body_html', $template, "Template '{$key}' missing body_html");
            $this->assertIsString($template['subject']);
            $this->assertIsString($template['body_html']);
            $this->assertNotSame('', $template['subject']);
            $this->assertNotSame('', $template['body_html']);
        }
    }

    /**
     * @param  array<string, mixed>  $variables
     */
    #[DataProvider('sampleVariablesProvider')]
    public function test_template_renders_non_empty_with_sample_variables(string $key, array $variables): void
    {
        $repo = Mockery::mock(EmailTemplateRepositoryContract::class);
        $repo->shouldReceive('findByKey')->andReturnNull();

        $service = new EmailTemplateRenderingService($repo, DefaultEmailTemplates::TEMPLATES);

        $rendered = $service->render($key, $variables);

        $this->assertNotSame('', $rendered->subject, "Rendered subject for '{$key}' was empty.");
        $this->assertNotSame('', $rendered->bodyHtml, "Rendered body for '{$key}' was empty.");
    }

    /**
     * @return iterable<string, array{0: string, 1: array<string, mixed>}>
     */
    public static function sampleVariablesProvider(): iterable
    {
        $appName = ['app' => ['name' => 'Polis']];
        $user = ['user' => ['first_name' => 'Ada', 'last_name' => 'Lovelace']];

        yield 'welcome' => ['welcome', $appName + $user];

        yield 'organization_manager_added' => ['organization_manager_added', $user + [
            'organization' => ['name' => 'Acme Co.'],
            'organization_role' => 'admin',
            'temp_password' => 'temp-pass-123',
        ]];

        yield 'renewal_reminder' => ['renewal_reminder', $user + [
            'membership_name' => 'Gold Tier',
            'membership_cost' => '$49.00',
            'recurring_message' => 'Your subscription will renew automatically.',
        ]];

        yield 'renewal_receipt' => ['renewal_receipt', $appName + $user + [
            'membership_name' => 'Gold Tier',
            'membership_cost' => '$49.00',
            'expiration_date' => 'January 1st 2027',
        ]];

        yield 'renewal_failure' => ['renewal_failure', $appName + $user + [
            'membership_name' => 'Gold Tier',
            'expiration_date' => 'January 1st 2027',
            'failure_reason' => 'Card declined.',
        ]];

        yield 'membership_expired' => ['membership_expired', $appName + $user + [
            'membership_name' => 'Gold Tier',
            'expiration_date' => 'January 1st 2027',
        ]];
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
