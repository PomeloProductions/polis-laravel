<?php

declare(strict_types=1);

namespace Polis\Repositories\Subscription;

use App\Models\Subscription\MembershipPlan;
use App\Models\Subscription\MembershipPlanRate;
use App\Models\Subscription\Subscription;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Polis\Contracts\Repositories\Subscription\MembershipPlanRateRepositoryContract;
use Polis\Contracts\Repositories\Subscription\SubscriptionRepositoryContract;
use Polis\Models\BaseModelAbstract;
use Polis\Repositories\BaseRepositoryAbstract;
use Psr\Log\LoggerInterface as LogContract;

/**
 * Class SubscriptionRepository
 */
class SubscriptionRepository extends BaseRepositoryAbstract implements SubscriptionRepositoryContract
{
    private MembershipPlanRateRepositoryContract $membershipPlanRateRepository;

    /**
     * SubscriptionRepository constructor.
     */
    public function __construct(Subscription $model, LogContract $log,
        MembershipPlanRateRepositoryContract $membershipPlanRateRepository)
    {
        parent::__construct($model, $log);
        $this->membershipPlanRateRepository = $membershipPlanRateRepository;
    }

    /**
     * Makes sure to set all meta data properly
     *
     * @return BaseModelAbstract
     */
    public function create(array $data = [], ?BaseModelAbstract $relatedModel = null, array $forcedValues = [])
    {
        /** @var MembershipPlanRate $membershipPlanRate */
        $membershipPlanRate = $this->membershipPlanRateRepository->findOrFail($data['membership_plan_rate_id']);
        $data['subscribed_at'] = Carbon::now();
        $data['last_renewed_at'] = Carbon::now();

        if (isset($data['is_trial']) && $data['is_trial'] && $membershipPlanRate->membershipPlan->trial_period) {
            $data['expires_at'] = Carbon::now()->addDays($membershipPlanRate->membershipPlan->trial_period);
        } else {
            $data['is_trial'] = false;
            switch ($membershipPlanRate->membershipPlan->duration) {
                case MembershipPlan::DURATION_MONTH:
                    $data['expires_at'] = Carbon::now()->addMonth();
                    break;
                case MembershipPlan::DURATION_YEAR:
                    $data['expires_at'] = Carbon::now()->addYear();
                    break;
            }
        }

        return parent::create($data, $relatedModel, $forcedValues);
    }

    /**
     * Finds all subscriptions that expire at a certain date
     */
    public function findExpiring(Carbon $expiresAt): Collection
    {
        return $this->model->newQuery()
            ->where('expires_at', '>=', Carbon::instance($expiresAt)->setTime(0, 0, 0))
            ->where('expires_at', '<=', Carbon::instance($expiresAt)->setTime(23, 59, 59))
            ->get();
    }

    /**
     * Finds all subscriptions that expire after the passed in expiration date
     *  The optional type field will filter out all subscriptions that are not to a specific subscriber type
     */
    public function findExpiresAfter(Carbon $expirationDate, ?string $type = null): Collection
    {
        $query = $this->model->newQuery()
            ->whereNull('expires_at')
            ->orWhere('expires_at', '>=', $expirationDate);

        if ($type) {
            $query->where('subscriber_type', '=', $type);
        }

        return $query->get();
    }
}
