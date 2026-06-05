<?php

declare(strict_types=1);

namespace Polis\Push;

/**
 * In-code default push notification templates.
 *
 * These act as the final fallback in PushTemplateRenderingService's
 * lookup hierarchy: org-specific DB row -> global DB row -> the entry
 * here -> throw.
 *
 * Why in-code defaults? Day-1 deploys of a new template-emitting feature
 * shouldn't require a DB seeding pass first; templates are still
 * editable at runtime via the admin UI (which writes PushTemplate rows),
 * but a working default ships with the code. Mirrors
 * Polis\Mail\DefaultEmailTemplates.
 *
 * Copy was extracted from PolisOS's existing
 * `ContactCreatedListener::handle` body construction (the hardcoded
 * `'{first} {last} wants to connect with you!'` string), with the inline
 * PHP expressions converted to Blade-style {{ var.path }} placeholders;
 * see PushTemplateRenderingService for the interpolation rules.
 *
 * To add a new template:
 *   1. Add an entry here keyed by the template's stable identifier (snake_case).
 *   2. Wire any new listener/job to call PushTemplateRenderingService with that key.
 *   3. Optionally seed a PushTemplate DB row to make it editable.
 */
final class DefaultPushTemplates
{
    /**
     * @var array<string, array{title: string, body: string}>
     */
    public const TEMPLATES = [
        'contact_created' => [
            'title' => 'New Contact Request!',
            'body' => '{{ contact.initiator.first_name }} {{ contact.initiator.last_name }} wants to connect with you!',
        ],
    ];
}
