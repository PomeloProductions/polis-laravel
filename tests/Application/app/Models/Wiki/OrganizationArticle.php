<?php

declare(strict_types=1);

namespace App\Models\Wiki;

/**
 * Policy-routing sentinel for the org-scoped Article ("contract") listing.
 *
 * The platform-wide GET /articles authorizes through App\Policies\Wiki\ArticlePolicy
 * (whose all()/view() return true for any authenticated user). The org-scoped
 * GET /organizations/{organization}/articles must instead authorize through
 * App\Policies\Wiki\OrganizationArticlePolicy (manage-the-org-or-super-admin).
 *
 * Both surfaces operate on the same Article model, and Laravel's gate resolves
 * a policy from the model class name (Models\ -> Policies\ + "Policy"). To route
 * the org request to OrganizationArticlePolicy WITHOUT hijacking the platform
 * ArticlePolicy, the org IndexRequest names THIS subclass as its policy model:
 * App\Models\Wiki\OrganizationArticle -> App\Policies\Wiki\OrganizationArticlePolicy.
 *
 * It is a pure authorization sentinel — it is never persisted or queried; the
 * controller/repository still work with the real Article model.
 */
class OrganizationArticle extends Article {}
