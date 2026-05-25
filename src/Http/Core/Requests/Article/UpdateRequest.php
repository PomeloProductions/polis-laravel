<?php

declare(strict_types=1);

namespace Polis\Http\Core\Requests\Article;

use App\Models\Wiki\Article;
use App\Policies\Wiki\ArticlePolicy;
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
        return ArticlePolicy::ACTION_UPDATE;
    }

    /**
     * Get the class name of the policy that this request utilizes
     */
    protected function getPolicyModel(): string
    {
        return Article::class;
    }

    /**
     * Gets any additional parameters needed for the policy function
     */
    protected function getPolicyParameters(): array
    {
        return [$this->route('article')];
    }

    /**
     * Gets the validation rules for this request
     */
    public function rules(Article $article): array
    {
        return $article->getValidationRules(Article::VALIDATION_RULES_UPDATE);
    }
}
