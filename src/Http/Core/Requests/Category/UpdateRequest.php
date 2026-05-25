<?php

declare(strict_types=1);

namespace Polis\Http\Core\Requests\Category;

use App\Models\Category;
use App\Policies\CategoryPolicy;
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
        return CategoryPolicy::ACTION_UPDATE;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(Category $category)
    {
        return $category->getValidationRules(Category::VALIDATION_RULES_UPDATE);
    }

    /**
     * {@inheritDoc}
     */
    protected function getPolicyModel(): string
    {
        return Category::class;
    }

    protected function getPolicyParameters(): array
    {
        return [
            $this->route('category'),
        ];
    }
}
