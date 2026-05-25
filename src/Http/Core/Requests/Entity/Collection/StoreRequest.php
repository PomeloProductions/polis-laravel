<?php

declare(strict_types=1);

namespace Polis\Http\Core\Requests\Entity\Collection;

use App\Models\Collection\Collection;
use App\Policies\Collection\CollectionPolicy;
use Polis\Contracts\Http\HasEntityInRequestContract;
use Polis\Http\Core\Requests\BaseAuthenticatedRequestAbstract;
use Polis\Http\Core\Requests\Entity\Traits\IsEntityRequestTrait;
use Polis\Http\Core\Requests\Traits\HasNoExpands;

/**
 * Class StoreRequest
 */
class StoreRequest extends BaseAuthenticatedRequestAbstract implements HasEntityInRequestContract
{
    use HasNoExpands, IsEntityRequestTrait;

    /**
     * Get the policy action for the guard
     */
    protected function getPolicyAction(): string
    {
        return CollectionPolicy::ACTION_CREATE;
    }

    /**
     * Get the class name of the policy that this request utilizes
     */
    protected function getPolicyModel(): string
    {
        return Collection::class;
    }

    /**
     * Gets any additional parameters needed for the policy function
     */
    protected function getPolicyParameters(): array
    {
        return [
            $this->getEntity(),
        ];
    }

    /**
     * The rules for the request
     *
     * @return array
     */
    public function rules(Collection $collection)
    {
        return $collection->getValidationRules(Collection::VALIDATION_RULES_CREATE);
    }
}
