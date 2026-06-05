<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Mail;

use Polis\Mail\RenderedEmail;
use Polis\Tests\TestCase;

/**
 * RenderedEmail is a readonly DTO; this verifies its constructor wiring.
 */
final class RenderedEmailTest extends TestCase
{
    public function test_exposes_subject_and_body_html(): void
    {
        $rendered = new RenderedEmail(subject: 'Hi', bodyHtml: '<p>Body</p>');

        $this->assertSame('Hi', $rendered->subject);
        $this->assertSame('<p>Body</p>', $rendered->bodyHtml);
    }
}
