<?php

declare(strict_types=1);

namespace App\Http\Core\Requests\Organization\Article;

use App\Models\Wiki\OrganizationArticle;
use Polis\Http\Core\Requests\Organization\Article\IndexRequest as BaseIndexRequest;

/**
 * Guards GET /organizations/{organization}/articles for the dummy app.
 *
 * The package base request declares Article as its policy model, which the gate
 * would resolve to the permissive platform ArticlePolicy. Override the policy
 * model with the OrganizationArticle sentinel so authorization runs through
 * App\Policies\Wiki\OrganizationArticlePolicy (manage-the-org / super-admin)
 * instead — the org-detail dashboard contract this PR hardens.
 */
class IndexRequest extends BaseIndexRequest
{
    protected function getPolicyModel(): string
    {
        return OrganizationArticle::class;
    }
}
