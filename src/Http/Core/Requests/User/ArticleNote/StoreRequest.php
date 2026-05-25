<?php

declare(strict_types=1);

namespace Polis\Http\Core\Requests\User\ArticleNote;

use App\Models\User\ArticleNote;
use App\Policies\User\ArticleNotePolicy;
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
        return ArticleNotePolicy::ACTION_CREATE;
    }

    /**
     * Get the class name of the policy that this request utilizes
     */
    protected function getPolicyModel(): string
    {
        return ArticleNote::class;
    }

    /**
     * Gets any additional parameters needed for the policy function
     */
    protected function getPolicyParameters(): array
    {
        return [
            $this->route('user'),
        ];
    }

    /**
     * @return array
     */
    public function rules(ArticleNote $model)
    {
        return $model->getValidationRules(ArticleNote::VALIDATION_RULES_CREATE);
    }
}
