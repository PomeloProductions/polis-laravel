<?php

declare(strict_types=1);

namespace Polis\Console\Commands;

use App\Models\Subscription\Subscription;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Mail\Mailer;
use Polis\Contracts\Repositories\Subscription\SubscriptionRepositoryContract;
use Polis\Mail\TemplatedMailable;

/**
 * Class SendRenewalReminders
 *
 * Daily command that emails users whose subscriptions expire two weeks from
 * today, reminding them of the upcoming renewal.
 *
 * Migrated from PolisOS's app/Console/Commands/SendRenewalReminders.php with
 * the email-sending path switched from MessageRepository->sendEmailToUser
 * (hardcoded blade view) to TemplatedMailable (runtime-editable template
 * via the `renewal_reminder` key in DefaultEmailTemplates).
 */
class SendRenewalReminders extends Command
{
    /** @var string */
    protected $signature = 'send-renewal-reminders';

    /** @var string */
    protected $name = 'Send Renewal Reminders';

    /** @var string */
    protected $description = 'Sends renewal reminders to all users that have a membership plan expiring in two weeks.';

    public function __construct(
        private readonly SubscriptionRepositoryContract $subscriptionRepository,
        private readonly Mailer $mailer,
        private readonly Repository $config,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $expirationCarbon = Carbon::now()->addWeeks(2);
        /** @var Subscription $subscription */
        foreach ($this->subscriptionRepository->findExpiring($expirationCarbon) as $subscription) {
            if ($subscription->subscriber_type !== 'user') {
                continue;
            }
            $subscriber = $subscription->subscriber;
            $plan = $subscription->membershipPlanRate->membershipPlan ?? null;
            $variables = [
                'user' => [
                    'first_name' => $subscriber->first_name ?? '',
                    'last_name' => $subscriber->last_name ?? '',
                ],
                'app' => [
                    'name' => $this->config->get('app.name', 'Polis'),
                ],
                'membership_name' => $plan->name ?? '',
                'membership_cost' => $subscription->formatted_cost ?? '',
                'recurring' => $subscription->recurring ? '1' : '',
                'recurring_message' => $subscription->recurring
                    ? 'Your subscription will renew automatically.'
                    : 'Your subscription will NOT renew automatically — please log in to renew.',
                'subscription' => [
                    'expires_at' => $subscription->formatted_expires_at ?? '',
                ],
            ];
            $this->mailer->to($subscriber->email)->send(new TemplatedMailable(
                templateKey: 'renewal_reminder',
                variables: $variables,
            ));
        }

        return self::SUCCESS;
    }
}
