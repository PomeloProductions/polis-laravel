<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Push;

use Polis\Push\DefaultPushTemplates;
use Polis\Tests\TestCase;

/**
 * Pin the structure of in-code push-template defaults.
 */
final class DefaultPushTemplatesTest extends TestCase
{
    public function test_contact_created_template_present(): void
    {
        $this->assertArrayHasKey('contact_created', DefaultPushTemplates::TEMPLATES);
    }

    public function test_every_template_has_title_and_body_keys(): void
    {
        foreach (DefaultPushTemplates::TEMPLATES as $key => $template) {
            $this->assertArrayHasKey('title', $template, "Template '{$key}' missing title");
            $this->assertArrayHasKey('body', $template, "Template '{$key}' missing body");
            $this->assertIsString($template['title']);
            $this->assertIsString($template['body']);
            $this->assertNotSame('', $template['title']);
            $this->assertNotSame('', $template['body']);
        }
    }
}
