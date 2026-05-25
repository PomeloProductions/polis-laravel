<?php

declare(strict_types=1);

namespace Polis\Http\Core\Requests\Wiki\ArticleSummary;

use App\Models\Wiki\ArticleSummary;
use App\Policies\Wiki\ArticleSummaryPolicy;
use Polis\Http\Core\Requests\BaseAuthenticatedRequestAbstract;
use Polis\Http\Core\Requests\Traits\HasNoExpands;

/**
 * Class UpdateRequest
 */
class UpdateRequest extends BaseAuthenticatedRequestAbstract
{
    use HasNoExpands;

    /**
     * Get the policy action for the guard
     */
    protected function getPolicyAction(): string
    {
        return ArticleSummaryPolicy::ACTION_UPDATE;
    }

    /**
     * Get the class name of the policy that this request utilizes
     */
    protected function getPolicyModel(): string
    {
        return ArticleSummary::class;
    }

    /**
     * Gets any additional parameters needed for the policy function
     */
    protected function getPolicyParameters(): array
    {
        return [
            $this->route('article'),
        ];
    }

    /**
     * The rules for this request
     */
    public function rules(ArticleSummary $model)
    {
        return $model->getValidationRules(ArticleSummary::VALIDATION_RULES_UPDATE);
    }
}
