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
}
