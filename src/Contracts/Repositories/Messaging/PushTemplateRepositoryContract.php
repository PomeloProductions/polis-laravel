<?php

declare(strict_types=1);

namespace Polis\Contracts\Repositories\Messaging;

use Polis\Contracts\Messaging\PushTemplateContract;
use Polis\Contracts\Repositories\BaseRepositoryContract;

/**
 * Interface PushTemplateRepositoryContract
 *
 * Mirrors EmailTemplateRepositoryContract. Push templates live in the same
 * `articles` table and share the additive `key` + `organization_id`
 * columns introduced for email templates. They are distinguished from
 * email templates and ordinary wiki articles by the PushTemplate model's
 * global scope (`whereNotNull('articles.key')`, matching EmailTemplate's
 * pattern) combined with a key-namespace convention: push template keys
 * must be unique across email + push (and may be prefixed `push_` in
 * applications that prefer explicit disambiguation).
 */
interface PushTemplateRepositoryContract extends BaseRepositoryContract
{
    /**
     * Look up a push notification template by its string key, preferring
     * an organization-scoped override when one exists.
     *
     * Lookup order:
     *   1. organization_id = $organizationId AND key = $key (if $organizationId given)
     *   2. organization_id IS NULL AND key = $key (global default)
     *   3. null (caller falls back to in-code DefaultPushTemplates)
     *
     * Returns a PushTemplateContract so callers can mock or supply
     * alternate implementations without coupling to the concrete Eloquent
     * model.
     */
    public function findByKey(string $key, ?int $organizationId = null): ?PushTemplateContract;
}
