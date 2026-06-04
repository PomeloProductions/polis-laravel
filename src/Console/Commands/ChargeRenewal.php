<?php

declare(strict_types=1);

namespace Polis\Console\Commands;

use App\Models\Subscription\Subscription;
use Carbon\Carbon;
use Cartalyst\Stripe\Exception\ApiLimitExceededException;
use Cartalyst\Stripe\Exception\CardErrorException;
use Cartalyst\Stripe\Exception\NotFoundException;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Support\Str;
use Polis\Contracts\Repositories\Messaging\MessageRepositoryContract;
use Polis\Contracts\Repositories\Subscription\SubscriptionRepositoryContract;
use Polis\Contracts\Services\StripePaymentServiceContract;
use Polis\Mail\TemplatedMailable;

/**
 * Class ChargeRenewal
 *
 * Daily command that walks every subscription expiring today and either
 * (a) auto-charges the user's stored Stripe payment method (recurring) or
 * (b) emails the user to let them know the membership has lapsed
 * (non-recurring).
 *
 * Migrated from PolisOS's app/Console/Commands/ChargeRenewal.php. The
 * success email path was switched from MessageRepository->sendEmailToUser
 * (hardcoded blade view) to TemplatedMailable using the `renewal_receipt`
 * key in DefaultEmailTemplates, matching the pattern established by
 * SendRenewalReminders in PR #2.
 *
 * Failure + expiration paths still use MessageRepositoryContract because
 * the corresponding template keys (`renewal_failure`, `membership_expired`)
 * have not been added to DefaultEmailTemplates yet. Migrating those is a
 * follow-up; the wiring here will swap to TemplatedMailable once the
 * template keys land.
 */
class ChargeRenewal extends Command
{
    /** @var string */
    protected $signature = 'charge-renewal';

    /** @var string */
    protected $name = 'Charge Renewals';

    /** @var string */
    protected $description = 'Attempts to charge all recurring renewals due today.';

    private string $appName;

    public function __construct(
        private readonly StripePaymentServiceContract $paymentService,
        private readonly SubscriptionRepositoryContract $subscriptionRepository,
        private readonly MessageRepositoryContract $messageRepository,
        private readonly Mailer $mailer,
        Repository $config,
    ) {
        parent::__construct();
        $this->appName = (string) $config->get('app.name', 'Polis');
    }

    /**
     * Sorts through every subscription expiring today, then either charges
     * the recurring ones or emails the user that their membership has
     * expired.
     */
    public function handle(): int
    {
        $tomorrow = Carbon::now()->addDay();
        /** @var Subscription $subscription */
        foreach ($this->subscriptionRepository->findExpiring(Carbon::now()) as $subscription) {
            if ($subscription->subscriber->currentSubscription($tomorrow)) {
                continue;
            }
            if ($subscription->recurring) {
                $this->chargeRecurring($subscription);
            } else {
                $this->sendExpirationEmail($subscription);
            }
        }

        return self::SUCCESS;
    }

    /**
     * Dispatches a recurring charge to the appropriate payment processor.
     */
    public function chargeRecurring(Subscription $subscription): void
    {
        if ($subscription->paymentMethod->payment_method_type === 'stripe') {
            $this->chargeStripe($subscription);
        } else {
            $this->checkPayPal($subscription);
        }
    }

    /**
     * Charges a subscription's stored Stripe payment method. On success,
     * emits the templated renewal receipt; on failure, falls back to
     * the legacy failure email path.
     */
    public function chargeStripe(Subscription $subscription): void
    {
        try {
            $this->paymentService->createPayment(
                $subscription->subscriber,
                $subscription->paymentMethod,
                'Subscription renewal for '.$subscription->membershipPlanRate->membershipPlan->name,
                [[
                    'item_id' => $subscription->id,
                    'item_type' => 'subscription',
                    'amount' => (float) $subscription->membershipPlanRate->cost,
                ]],
            );
            $this->handleSuccess($subscription);
        } catch (NotFoundException $e) {
            $this->sendFailureEmail($subscription, 'Renewal card no longer on file.');
        } catch (CardErrorException $e) {
            $this->sendFailureEmail($subscription, $e->getMessage());
        } catch (ApiLimitExceededException $e) {
            $sleepTime = $this->getLaravel()->environment() === 'production' ? 60 : 0;
            $this->reattemptCharge($subscription, $sleepTime);
        } catch (Exception $e) {
            $this->sendFailureEmail($subscription, 'Unknown Error');
        }
    }

    /**
     * Placeholder for PayPal recurring reconciliation.
     *
     * @todo check into PayPal if a payment should have been made there
     */
    public function checkPayPal(Subscription $subscription): void
    {
        // intentionally left as a no-op; ported from PolisOS as-is.
    }

    /**
     * Updates the subscription record after a successful charge and
     * dispatches the renewal_receipt templated email.
     */
    public function handleSuccess(Subscription $subscription): void
    {
        /** @var Subscription $updatedSubscription */
        $updatedSubscription = $this->subscriptionRepository->update($subscription, [
            'last_renewed_at' => Carbon::now(),
            'expires_at' => Carbon::now()->addYear(),
            'is_trial' => false,
        ]);

        $subscriber = $updatedSubscription->subscriber;
        $plan = $updatedSubscription->membershipPlanRate->membershipPlan ?? null;

        $variables = [
            'user' => [
                'first_name' => $subscriber->first_name ?? '',
                'last_name' => $subscriber->last_name ?? '',
            ],
            'app' => [
                'name' => $this->appName,
            ],
            'membership_name' => $plan->name ?? '',
            'membership_cost' => $updatedSubscription->formatted_cost ?? '',
            'expiration_date' => $updatedSubscription->formatted_expires_at ?? '',
        ];

        $this->mailer->to($subscriber->email)->send(new TemplatedMailable(
            templateKey: 'renewal_receipt',
            variables: $variables,
        ));
    }

    /**
     * Retries a Stripe charge after a configurable backoff (only used when
     * the API surfaces a rate-limit exception).
     */
    public function reattemptCharge(Subscription $subscription, int $seconds = 0): void
    {
        sleep($seconds);
        $this->chargeStripe($subscription);
    }

    /**
     * Notifies a user whose non-recurring membership has just lapsed.
     *
     * @todo migrate to TemplatedMailable once a `membership_expired`
     *       default template lands in DefaultEmailTemplates.
     */
    public function sendExpirationEmail(Subscription $subscription): void
    {
        $this->sendSubscriberEmail($subscription, $this->appName.' Membership Expired', 'membership-expired', [
            'membership_name' => $subscription->membershipPlanRate->membershipPlan->name,
        ]);
    }

    /**
     * Notifies a user that an auto-renewal charge failed.
     *
     * @todo migrate to TemplatedMailable once a `renewal_failure` default
     *       template lands in DefaultEmailTemplates.
     */
    public function sendFailureEmail(Subscription $subscription, string $reason): void
    {
        if (! Str::endsWith($reason, '.')) {
            $reason .= '.';
        }
        $this->sendSubscriberEmail($subscription, $this->appName.' Membership Renewal Failed', 'membership-renewal-failure', [
            'membership_name' => $subscription->membershipPlanRate->membershipPlan->name,
            'reason' => $reason,
        ]);
    }

    /**
     * Routes a legacy (non-template-key) email through the message
     * repository, but only for `user` subscribers. Other subscriber types
     * are silently skipped, preserving PolisOS behaviour.
     */
    private function sendSubscriberEmail(Subscription $subscription, string $subject, string $template, array $baseData): void
    {
        if ($subscription->subscriber_type === 'user') {
            $this->messageRepository->sendEmailToUser($subscription->subscriber, $subject, $template, $baseData);
        }
    }
}
