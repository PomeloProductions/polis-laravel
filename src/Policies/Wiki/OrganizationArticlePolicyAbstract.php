<?php

declare(strict_types=1);

namespace Polis\Policies\Wiki;

use Polis\Policies\BaseBelongsToOrganizationPolicyAbstract;
use Polis\Policies\BasePolicyAbstract;

/**
 * Class OrganizationArticlePolicyAbstract
 *
 * Authorizes the org-scoped Article ("contract") endpoints hung under
 * `/organizations/{organization}/articles`.
 *
 * This is intentionally SEPARATE from {@see ArticlePolicyAbstract}, which
 * governs the platform-wide `/articles` wiki surface (where `all`/`view`
 * return true for any authenticated user). The org-detail dashboard needs the
 * opposite guarantee: an Article may only be listed/viewed through an
 * organization the caller actually manages.
 *
 * By extending {@see BaseBelongsToOrganizationPolicyAbstract} we inherit the
 * canonical org-scoping rules for free:
 *
 *   - platform super-admins  -> allowed for ANY organization
 *     (via {@see BasePolicyAbstract::before()}),
 *   - org ADMINISTRATOR / MANAGER -> allowed for THEIR organization only
 *     (via User::canManageOrganization()),
 *   - everyone else -> denied,
 *   - view/update/delete additionally assert the Article's organization_id
 *     matches the organization in the route, preventing cross-tenant access.
 *
 * Consumers register a concrete `App\Policies\Wiki\OrganizationArticlePolicy`
 * for their App\Models\Wiki\Article, exactly like every other package policy.
 */
abstract class OrganizationArticlePolicyAbstract extends BaseBelongsToOrganizationPolicyAbstract {}
