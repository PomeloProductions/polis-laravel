<?php

declare(strict_types=1);

namespace Polis\Push;

/**
 * DTO returned by PushTemplateRenderingService::render() — carries the
 * final interpolated title and plain-text body ready for handoff to a
 * push notification dispatch service (e.g. SendPushNotificationService).
 *
 * Mirrors Polis\Mail\RenderedEmail. The shape is intentionally minimal:
 * a title and a body. Platform-specific metadata (sound, icon,
 * click_action, etc.) lives at the call site, not in the rendered
 * template.
 */
final class RenderedPushNotification
{
    public function __construct(
        public readonly string $title,
        public readonly string $body,
    ) {}
}
