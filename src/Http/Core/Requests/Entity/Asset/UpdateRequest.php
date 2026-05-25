<?php

declare(strict_types=1);

namespace Polis\Http\Core\Requests\Entity\Asset;

use App\Models\Asset;
use App\Policies\AssetPolicy;
use Polis\Contracts\Http\HasEntityInRequestContract;
use Polis\Http\Core\Requests\BaseAuthenticatedRequestAbstract;
use Polis\Http\Core\Requests\Entity\Traits\IsEntityRequestTrait;
use Polis\Http\Core\Requests\Traits\HasNoExpands;

/**
 * Class UpdateRequest
 */
class UpdateRequest extends BaseAuthenticatedRequestAbstract implements HasEntityInRequestContract
{
    use HasNoExpands, IsEntityRequestTrait;

    /**
     * Get the policy action for the guard
     */
    protected function getPolicyAction(): string
    {
        return AssetPolicy::ACTION_UPDATE;
    }

    /**
     * Get the class name of the policy that this request utilizes
     */
    protected function getPolicyModel(): string
    {
        return Asset::class;
    }

    /**
     * Gets any additional parameters needed for the policy function
     */
    protected function getPolicyParameters(): array
    {
        return [
            $this->getEntity(),
            $this->route('asset'),
        ];
    }

    /**
     * The rules for this request
     *
     * @return array
     */
    public function rules(Asset $model)
    {
        return $model->getValidationRules(Asset::VALIDATION_RULES_UPDATE);
    }
}
