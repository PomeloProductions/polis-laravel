<?php

declare(strict_types=1);

namespace Polis\Contracts\Messaging;

/**
 * Read-only contract surface for a renderable email template.
 *
 * Decouples the EmailTemplateRenderingService from the concrete
 * Polis\Models\Messaging\EmailTemplate Eloquent model — useful both for
 * testability (the test suite can satisfy this with a tiny fake) and for
 * future extension (e.g. an external-provider-backed template source).
 *
 * The concrete EmailTemplate model exposes `subject` and `body_html` as
 * accessor-driven attributes; this interface formalizes that surface.
 */
interface EmailTemplateContract
{
    public function getSubject(): ?string;

    public function getBodyHtml(): ?string;
}
