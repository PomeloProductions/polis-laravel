<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Mail;

use Polis\Mail\DefaultEmailTemplates;
use Polis\Tests\TestCase;

/**
 * Pin the structure of in-code email-template defaults. Changes here are
 * intentional template content shifts — keep the shape stable so consumer
 * code reading TEMPLATES[$key] can rely on `subject` and `body_html`.
 */
final class DefaultEmailTemplatesTest extends TestCase
{
    public function test_templates_constant_exposes_expected_keys(): void
    {
        $keys = array_keys(DefaultEmailTemplates::TEMPLATES);

        $this->assertContains('welcome', $keys);
        $this->assertContains('organization_manager_added', $keys);
        $this->assertContains('organization_manager_invited', $keys);
        $this->assertContains('renewal_reminder', $keys);
        $this->assertContains('renewal_receipt', $keys);
    }

    public function test_invitation_template_references_accept_url_and_role_variables(): void
    {
        $template = DefaultEmailTemplates::TEMPLATES['organization_manager_invited'];

        $this->assertStringContainsString('{{ accept_url }}', $template['body_html']);
        $this->assertStringContainsString('{{ organization.name }}', $template['body_html']);
        $this->assertStringContainsString('{{ organization_role }}', $template['body_html']);
        $this->assertStringContainsString('{{ inviter.name }}', $template['body_html']);
        $this->assertStringContainsString('{{ organization.name }}', $template['subject']);
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
}
