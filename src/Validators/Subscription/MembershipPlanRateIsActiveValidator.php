<?php

declare(strict_types=1);

namespace Polis\Validators\Subscription;

use App\Models\Subscription\MembershipPlanRate;
use Illuminate\Contracts\Validation\Validator;
use Polis\Contracts\Repositories\Subscription\MembershipPlanRateRepositoryContract;
use Polis\Validators\BaseValidatorAbstract;

/**
 * Class MembershipPlanRateIsActiveValidator
 */
class MembershipPlanRateIsActiveValidator extends BaseValidatorAbstract
{
    /**
     * The key this is registered at
     */
    const KEY = 'membership_plan_rate_is_active';

    /**
     * @var MembershipPlanRateRepositoryContract
     */
    private $membershipPlanRateRepository;

    /**
     * MembershipPlanRateIsActiveValidator constructor.
     */
    public function __construct(MembershipPlanRateRepositoryContract $membershipPlanRateRepository)
    {
        $this->membershipPlanRateRepository = $membershipPlanRateRepository;
    }

    /**
     * Responds to 'membership_plan_rate_is_active', and must be attached to the token field
     *
     * @param  array  $parameters
     * @return bool
     */
    public function validate($attribute, $value, $parameters = [], ?Validator $validator = null)
    {
        $this->ensureValidatorAttribute('membership_plan_rate_id', $attribute);

        try {
            /** @var MembershipPlanRate $membershipPlanRate */
            $membershipPlanRate = $this->membershipPlanRateRepository->findOrFail($value);

            return $membershipPlanRate->active;

        } catch (\Exception $e) {
            return false;
        }
    }
}
