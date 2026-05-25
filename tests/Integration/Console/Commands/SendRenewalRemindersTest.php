<?php

declare(strict_types=1);

namespace Polis\Tests\Integration\Console\Commands;

use App\Console\Commands\SendRenewalReminders;
use App\Models\Subscription\MembershipPlanRate;
use App\Models\Subscription\Subscription;
use Carbon\Carbon;
use Polis\Contracts\Repositories\Messaging\MessageRepositoryContract;
use Polis\Repositories\Subscription\MembershipPlanRateRepository;
use Polis\Repositories\Subscription\SubscriptionRepository;
use Polis\Tests\CustomMockInterface;
use Polis\Tests\DatabaseSetupTrait;
use Polis\Tests\TestCase;

/**
 * Class SendRenewalRemindersTest
 */
final class SendRenewalRemindersTest extends TestCase
{
    use DatabaseSetupTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();
    }

    public function test_handle(): void
    {
        $subscriptionRepository = new SubscriptionRepository(
            new Subscription,
            $this->getGenericLogMock(),
            new MembershipPlanRateRepository(
                new MembershipPlanRate,
                $this->getGenericLogMock(),
            )
        );
        /** @var MessageRepositoryContract|CustomMockInterface $messageRepository */
        $messageRepository = mock(MessageRepositoryContract::class);

        $command = new SendRenewalReminders($subscriptionRepository, $messageRepository);

        /** @var Subscription $subscription */
        $subscription = Subscription::factory()->create([
            'expires_at' => Carbon::now()->addWeek(2),
            'membership_plan_rate_id' => MembershipPlanRate::factory()->create()->id,
        ]);
        Subscription::factory()->create([
            'expires_at' => Carbon::now()->addWeek()->addDay(6),
            'membership_plan_rate_id' => MembershipPlanRate::factory()->create()->id,
        ]);
        Subscription::factory()->create([
            'expires_at' => Carbon::now()->addWeek(2)->addDay(1),
            'membership_plan_rate_id' => MembershipPlanRate::factory()->create()->id,
        ]);

        $messageRepository->shouldReceive('sendEmailToUser')->once()->with(
            \Mockery::on(function ($user) use ($subscription) {
                $this->assertEquals($user, $subscription->subscriber);

                return true;
            }),
            'Membership Renewal Reminder',
            'renewal-reminder',
            \Mockery::on(function ($data) {

                $this->assertArrayHasKey('membership_name', $data);
                $this->assertArrayHasKey('recurring', $data);
                $this->assertArrayHasKey('membership_cost', $data);

                return true;
            })
        );

        $command->handle();
    }
}
