<?php

declare(strict_types=1);

namespace Polis\Http\Core\Requests\Article;

use App\Models\Wiki\Article;
use App\Policies\Wiki\ArticlePolicy;
use Polis\Http\Core\Requests\BaseAuthenticatedRequestAbstract;
use Polis\Http\Core\Requests\Traits\HasNoExpands;
use Polis\Http\Core\Requests\Traits\HasNoPolicyParameters;

/**
 * Class StoreRequest
 */
class StoreRequest extends BaseAuthenticatedRequestAbstract
{
    use HasNoExpands, HasNoPolicyParameters;

    /**
     * Get the policy action for the guard
     */
    protected function getPolicyAction(): string
    {
        return ArticlePolicy::ACTION_CREATE;
    }

    /**
     * Get the class name of the policy that this request utilizes
     */
    protected function getPolicyModel(): string
    {
        return Article::class;
    }

    /**
     * Get validation rules for the create request
     */
    public function rules(Article $article): array
    {
        return $article->getValidationRules(Article::VALIDATION_RULES_CREATE);
    }
}
