<?php

declare(strict_types=1);

namespace Polis\Http\Core\Requests\Category;

use App\Models\Category;
use App\Policies\CategoryPolicy;
use Polis\Http\Core\Requests\BaseAuthenticatedRequestAbstract;
use Polis\Http\Core\Requests\Traits\HasNoExpands;
use Polis\Http\Core\Requests\Traits\HasNoPolicyParameters;
use Polis\Http\Core\Requests\Traits\RejectsUnknownParams;

/**
 * Class StoreRequest
 */
class StoreRequest extends BaseAuthenticatedRequestAbstract
{
    use HasNoExpands, HasNoPolicyParameters, RejectsUnknownParams;

    /**
     * Get the policy action for the guard
     */
    protected function getPolicyAction(): string
    {
        return CategoryPolicy::ACTION_CREATE;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(Category $category)
    {
        return $category->getValidationRules(Category::VALIDATION_RULES_CREATE);
    }

    /**
     * {@inheritDoc}
     */
    protected function getPolicyModel(): string
    {
        return Category::class;
    }
}
