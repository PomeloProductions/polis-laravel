<?php

declare(strict_types=1);

namespace Polis\Mail;

/**
 * DTO returned by EmailTemplateRenderingService::render() — carries the final
 * interpolated subject and HTML body ready for handoff to a Mailable.
 */
final class RenderedEmail
{
    public function __construct(
        public readonly string $subject,
        public readonly string $bodyHtml,
    ) {}
}
