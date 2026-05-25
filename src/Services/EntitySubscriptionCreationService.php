<?php

declare(strict_types=1);

namespace Polis\Services;

use App\Models\Subscription\Subscription;
use Carbon\Carbon;
use Polis\Contracts\Models\IsAnEntityContract;
use Polis\Contracts\Repositories\Subscription\SubscriptionRepositoryContract;
use Polis\Contracts\Services\EntitySubscriptionCreationServiceContract;
use Polis\Contracts\Services\ProratingCalculationServiceContract;
use Polis\Contracts\Services\StripePaymentServiceContract;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

/**
 * Class EntitySubscriptionCreationService
 */
class EntitySubscriptionCreationService implements EntitySubscriptionCreationServiceContract
{
    private ProratingCalculationServiceContract $proratingCalculationService;

    private SubscriptionRepositoryContract $subscriptionRepository;

    private StripePaymentServiceContract $stripePaymentService;

    /**
     * EntitySubscriptionCreationService constructor.
     */
    public function __construct(ProratingCalculationServiceContract $proratingCalculationService,
        SubscriptionRepositoryContract $subscriptionRepository,
        StripePaymentServiceContract $stripePaymentService)
    {
        $this->proratingCalculationService = $proratingCalculationService;
        $this->subscriptionRepository = $subscriptionRepository;
        $this->stripePaymentService = $stripePaymentService;
    }

    /**
     * Creates a subscription for an entity while replacing any current one that may exist for an entity
     */
    public function createSubscription(IsAnEntityContract $entity, array $data): Subscription
    {
        $currentSubscription = $entity->currentSubscription(Carbon::now()->endOfDay());

        $data['subscriber_id'] = $entity->id;
        $data['subscriber_type'] = $entity->morphRelationName();

        $model = null;
        try {

            /** @var Subscription $model */
            $model = $this->subscriptionRepository->create($data);
            if ($currentSubscription && ! $model->isLifetime()) {
                $data['expires_at'] = $currentSubscription->expires_at;
                $data['recurring'] = $currentSubscription->recurring;
                $amount = $this->proratingCalculationService->calculateMembershipUpgradeCharge(
                    $currentSubscription,
                    $model->membershipPlanRate->membershipPlan
                );
            } else {
                $amount = (float) $model->membershipPlanRate->cost;
            }

            if (! $model->is_trial) {
                $this->stripePaymentService->createPayment($entity, $model->paymentMethod,
                    'Subscription Payment for '.$model->membershipPlanRate->membershipPlan->name, [
                        [
                            'item_id' => $model->id,
                            'item_type' => 'subscription',
                            'amount' => $amount,
                        ],
                    ]);
            }

            if ($currentSubscription) {
                $this->subscriptionRepository->update($currentSubscription, [
                    'canceled_at' => Carbon::now(),
                ]);
            }

        } catch (\Exception $e) {
            if ($model) {
                $this->subscriptionRepository->delete($model);
            }
            throw new ServiceUnavailableHttpException(5, 'Unable to accept payments right now');
        }

        return $model;
    }
}
