<?php
declare(strict_types=1);

namespace App\Console\Commands;

use Polis\Contracts\Repositories\Messaging\MessageRepositoryContract;
use Polis\Contracts\Repositories\Subscription\SubscriptionRepositoryContract;
use App\Models\Subscription\Subscription;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Class SendRenewalReminders
 * @package App\Console\Commands
 */
class SendRenewalReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send-renewal-reminders';

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'Send Renewal Reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sends renewal reminders to all users that have a membership plan expiring in two weeks.';

    /**
     * @var SubscriptionRepositoryContract
     */
    private $subscriptionRepository;

    /**
     * @var MessageRepositoryContract
     */
    private $messageRepository;

    /**
     * SendRenewalReminders constructor.
     * @param SubscriptionRepositoryContract $subscriptionRepository
     * @param MessageRepositoryContract $messageRepository
     */
    public function __construct(SubscriptionRepositoryContract $subscriptionRepository,
                                MessageRepositoryContract $messageRepository)
    {
        parent::__construct();
        $this->subscriptionRepository = $subscriptionRepository;
        $this->messageRepository = $messageRepository;
    }

    /**
     * Loops through all expiring subscriptions and send emails to those users
     */
    public function handle()
    {
        $expirationCarbon = Carbon::now()->addWeek(2);
        /** @var Subscription $subscription */
        foreach ($this->subscriptionRepository->findExpiring($expirationCarbon) as $subscription) {
            if ($subscription->subscriber_type == 'user') {
                $this->messageRepository->sendEmailToUser(
                    $subscription->subscriber,
                    'Membership Renewal Reminder',
                    'renewal-reminder',
                    [
                        'membership_name' => $subscription->membershipPlanRate->membershipPlan->name,
                        'recurring' => $subscription->recurring,
                        'membership_cost' => $subscription->formatted_cost,
                    ]
                );
            }
        }
    }
}