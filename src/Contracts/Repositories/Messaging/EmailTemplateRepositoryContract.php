<?php

declare(strict_types=1);

namespace Polis\Contracts\Repositories\Messaging;

use Polis\Contracts\Messaging\EmailTemplateContract;
use Polis\Contracts\Repositories\BaseRepositoryContract;

/**
 * Interface EmailTemplateRepositoryContract
 */
interface EmailTemplateRepositoryContract extends BaseRepositoryContract
{
    /**
     * Look up an email template by its string key, preferring an
     * organization-scoped override when one exists.
     *
     * Lookup order:
     *   1. organization_id = $organizationId AND key = $key (if $organizationId given)
     *   2. organization_id IS NULL AND key = $key (global default)
     *   3. null (caller falls back to in-code DefaultEmailTemplates)
     *
     * Returns an EmailTemplateContract so callers can mock or supply
     * alternate implementations without coupling to the concrete Eloquent
     * model.
     */
    public function findByKey(string $key, ?int $organizationId = null): ?EmailTemplateContract;

    /**
     * Look up the org-scoped EmailTemplate row for the given key — without
     * falling back to the global row. Returns null when no override
     * exists. Used by the admin API so the controller can distinguish an
     * org-scoped row from a global row when shaping the response.
     */
    public function findOrgScopedByKey(string $key, int $organizationId): ?EmailTemplateContract;

    /**
     * Upsert the org-scoped EmailTemplate row for the given key. If a
     * row already exists for ($key, $organizationId) it is updated;
     * otherwise a new row is created. Returns the resulting template.
     *
     * The "title" arg maps to the EmailTemplate `subject` accessor and
     * the underlying article title; "bodyHtml" maps to the article's
     * iteration content (the body of the email).
     */
    public function upsertOrgScoped(
        string $key,
        int $organizationId,
        string $subject,
        string $bodyHtml,
    ): EmailTemplateContract;

    /**
     * Delete the org-scoped EmailTemplate row for ($key, $organizationId)
     * if it exists. Used to revert an organization's override back to the
     * global default (or further down to the in-code default). Returns
     * true when a row was found + deleted, false when no row existed.
     */
    public function deleteOrgScoped(string $key, int $organizationId): bool;

    /**
     * Return the set of distinct `key` values that have at least one row
     * in the `articles` table scoped to the given organization OR to the
     * global (null organization_id) bucket. Used by the admin API to
     * surface every key the org could potentially edit.
     *
     * @return list<string>
     */
    public function listKeysForOrganization(int $organizationId): array;
}
