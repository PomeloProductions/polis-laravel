<?php

declare(strict_types=1);

namespace Polis\Http\Core\Requests\Category;

use App\Models\Category;
use App\Policies\CategoryPolicy;
use Polis\Http\Core\Requests\BaseAuthenticatedRequestAbstract;
use Polis\Http\Core\Requests\Traits\HasNoExpands;
use Polis\Http\Core\Requests\Traits\HasNoRules;

/**
 * Class DeleteRequest
 */
class DeleteRequest extends BaseAuthenticatedRequestAbstract
{
    use HasNoExpands, HasNoRules;

    /**
     * Get the policy action for the guard
     */
    protected function getPolicyAction(): string
    {
        return CategoryPolicy::ACTION_DELETE;
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
