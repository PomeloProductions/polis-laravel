<?php

declare(strict_types=1);

namespace Polis\Http\Core\Requests\Article\ArticleVersion;

use App\Models\Wiki\ArticleVersion;
use App\Policies\Wiki\ArticleVersionPolicy;
use Polis\Http\Core\Requests\BaseAuthenticatedRequestAbstract;
use Polis\Http\Core\Requests\Traits\HasNoExpands;

/**
 * Class StoreRequest
 */
class StoreRequest extends BaseAuthenticatedRequestAbstract
{
    use HasNoExpands;

    /**
     * Get the policy action for the guard
     */
    protected function getPolicyAction(): string
    {
        return ArticleVersionPolicy::ACTION_CREATE;
    }

    /**
     * Get the class name of the policy that this request utilizes
     */
    protected function getPolicyModel(): string
    {
        return ArticleVersion::class;
    }

    /**
     * Get validation rules for the create request
     */
    public function rules(ArticleVersion $articleVersion): array
    {
        return $articleVersion->getValidationRules(ArticleVersion::VALIDATION_RULES_CREATE);
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
}
