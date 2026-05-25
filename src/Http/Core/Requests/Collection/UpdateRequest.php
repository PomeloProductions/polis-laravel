<?php

declare(strict_types=1);

namespace Polis\Http\Core\Requests\Collection;

use App\Models\Collection\Collection;
use App\Policies\Collection\CollectionPolicy;
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
        return CollectionPolicy::ACTION_UPDATE;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(Collection $collection)
    {
        return $collection->getValidationRules(Collection::VALIDATION_RULES_UPDATE);
    }

    /**
     * {@inheritDoc}
     */
    protected function getPolicyModel(): string
    {
        return Collection::class;
    }

    protected function getPolicyParameters(): array
    {
        return [
            $this->route('collection'),
        ];
    }
}
