<?php

declare(strict_types=1);

namespace Polis\Http\Core\Requests\Entity\PaymentMethod;

use App\Models\Payment\PaymentMethod;
use App\Policies\Payment\PaymentMethodPolicy;
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
        return PaymentMethodPolicy::ACTION_UPDATE;
    }

    /**
     * Get the class name of the policy that this request utilizes
     */
    protected function getPolicyModel(): string
    {
        return PaymentMethod::class;
    }

    /**
     * Gets any additional parameters needed for the policy function
     */
    protected function getPolicyParameters(): array
    {
        return [
            $this->getEntity(),
            $this->route('payment_method'),
        ];
    }

    /**
     * Get validation rules for the create request
     */
    public function rules(PaymentMethod $paymentMethod): array
    {
        return $paymentMethod->getValidationRules(PaymentMethod::VALIDATION_RULES_UPDATE);
    }
}
