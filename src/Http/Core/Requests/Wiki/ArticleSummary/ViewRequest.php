<?php

declare(strict_types=1);

namespace Polis\Http\Core\Requests\Wiki\ArticleSummary;

use App\Models\Wiki\ArticleSummary;
use App\Policies\Wiki\ArticleSummaryPolicy;
use Polis\Http\Core\Requests\BaseAuthenticatedRequestAbstract;
use Polis\Http\Core\Requests\Traits\HasNoExpands;
use Polis\Http\Core\Requests\Traits\HasNoRules;

/**
 * Class ViewRequest
 */
class ViewRequest extends BaseAuthenticatedRequestAbstract
{
    use HasNoExpands, HasNoRules;

    /**
     * Get the policy action for the guard
     */
    protected function getPolicyAction(): string
    {
        return ArticleSummaryPolicy::ACTION_VIEW;
    }

    /**
     * {@inheritDoc}
     */
    protected function getPolicyModel(): string
    {
        return ArticleSummary::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getPolicyParameters(): array
    {
        return [
            $this->route('article'),
        ];
    }
}
