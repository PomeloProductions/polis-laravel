<?php

declare(strict_types=1);

namespace Polis\Validators\Subscription;

use App\Models\Payment\PaymentMethod;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Request;
use Polis\Contracts\Http\HasEntityInRequestContract;
use Polis\Contracts\Repositories\Payment\PaymentMethodRepositoryContract;
use Polis\Validators\BaseValidatorAbstract;
use Polis\Validators\Traits\HasEntityInRequestTrait;

/**
 * Class PaymentMethodIsOwnedByEntityValidator
 */
class PaymentMethodIsOwnedByEntityValidator extends BaseValidatorAbstract implements HasEntityInRequestContract
{
    use HasEntityInRequestTrait;

    /**
     * The key this is registered at
     */
    const KEY = 'payment_method_is_owned_by_entity';

    /**
     * @var PaymentMethodRepositoryContract
     */
    private $paymentMethodRepository;

    /**
     * @var Request
     */
    private $request;

    /**
     * PaymentMethodIsOwnedByUser constructor.
     */
    public function __construct(PaymentMethodRepositoryContract $paymentMethodRepository, Request $request)
    {
        $this->paymentMethodRepository = $paymentMethodRepository;
        $this->request = $request;
    }

    /**
     * Responds to 'payment_method_is_owned_by_user', and must be attached to the token field
     *
     * @param  array  $parameters
     * @return bool
     */
    public function validate($attribute, $value, $parameters = [], ?Validator $validator = null)
    {
        $this->ensureValidatorAttribute('payment_method_id', $attribute);

        try {
            /** @var PaymentMethod $paymentMethod */
            $paymentMethod = $this->paymentMethodRepository->findOrFail($value);

            $entity = $this->getEntity();

            return $entity->id == $paymentMethod->owner_id && $paymentMethod->owner_type == $entity->morphRelationName();

        } catch (\Exception $e) {
            return false;
        }
    }
}
