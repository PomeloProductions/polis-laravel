<?php

declare(strict_types=1);

namespace Polis\Contracts\Messaging;

/**
 * Read-only contract surface for a renderable push notification template.
 *
 * Mirrors EmailTemplateContract, but with push-specific fields: a `title`
 * (the notification headline) and a plain-text `body` (the notification
 * message). Push notifications do not have HTML bodies — they're flat
 * strings delivered to platform notification services (APNs/FCM/etc.).
 *
 * Decouples PushTemplateRenderingService from the concrete
 * Polis\Models\Messaging\PushTemplate Eloquent model — useful for
 * testability (the test suite satisfies this with a tiny fake) and for
 * future extension (e.g. an external-provider-backed template source).
 */
interface PushTemplateContract
{
    public function getTitle(): ?string;

    public function getBody(): ?string;
}
