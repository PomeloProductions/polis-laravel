<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Console\Commands;

use Carbon\Carbon;
use Cartalyst\Stripe\Exception\CardErrorException;
use Cartalyst\Stripe\Exception\NotFoundException;
use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Mail\PendingMail;
use Mockery;
use Polis\Console\Commands\ChargeRenewal;
use Polis\Contracts\Models\IsAnEntityContract;
use Polis\Contracts\Repositories\Subscription\SubscriptionRepositoryContract;
use Polis\Contracts\Services\StripePaymentServiceContract;
use Polis\Mail\TemplatedMailable;
use Polis\Tests\Fixtures\Models\Payment as PaymentFixture;
use Polis\Tests\Fixtures\Models\PaymentMethod as PaymentMethodFixture;
use Polis\Tests\Fixtures\Models\Subscription as SubscriptionFixture;
use Polis\Tests\Fixtures\Models\User as UserFixture;
use Polis\Tests\TestCase;
use ReflectionClass;
use ReflectionNamedType;
use stdClass;

/**
 * Class ChargeRenewalTest
 *
 * Unit coverage for the ChargeRenewal command: structural assertions about
 * the constructor + class shape (namespace, signature, contract-only
 * dependencies) plus behavioural tests that exercise handle() against the
 * contract surface via fixture-backed mocks.
 *
 * All three email paths (success → renewal_receipt, failure →
 * renewal_failure, expiration → membership_expired) now go through
 * TemplatedMailable. MessageRepositoryContract is no longer a constructor
 * dependency.
 *
 * The behavioural tests rely on fixture stubs registered in tests/bootstrap.php
 * so that App\Models\* type hints in the Polis contracts resolve inside this
 * package's standalone Testbench harness. See tests/Fixtures/README.md.
 *
 * The pre-existing integration test at
 * tests/Integration/Console/Commands/ChargeRenewalTest.php still exercises
 * the old (App-namespaced) command inside PolisOS's Consumer-Only suite.
 */
final class ChargeRenewalTest extends TestCase
{
    public function test_command_exists_in_polis_namespace(): void
    {
        $this->assertTrue(class_exists(ChargeRenewal::class));
        $this->assertTrue(is_subclass_of(ChargeRenewal::class, Command::class));
    }

    public function test_constructor_only_depends_on_polis_contracts_and_framework_abstractions(): void
    {
        $constructor = (new ReflectionClass(ChargeRenewal::class))->getConstructor();
        $this->assertNotNull($constructor);

        $expectedTypes = [
            StripePaymentServiceContract::class,
            SubscriptionRepositoryContract::class,
            Mailer::class,
            Repository::class,
        ];

        $actualTypes = [];
        foreach ($constructor->getParameters() as $param) {
            $type = $param->getType();
            $this->assertInstanceOf(ReflectionNamedType::class, $type);
            $actualTypes[] = $type->getName();
        }

        $this->assertSame($expectedTypes, $actualTypes);
    }

    public function test_command_dispatches_all_three_email_paths_via_templated_mailable(): void
    {
        $source = file_get_contents(
            (new ReflectionClass(ChargeRenewal::class))->getFileName(),
        );

        $this->assertStringContainsString(
            'use '.TemplatedMailable::class.';',
            $source,
            'ChargeRenewal must import TemplatedMailable to dispatch templated emails.',
        );
        $this->assertStringContainsString(
            "'renewal_receipt'",
            $source,
            'ChargeRenewal must reference the renewal_receipt template key on the success path.',
        );
        $this->assertStringContainsString(
            "'renewal_failure'",
            $source,
            'ChargeRenewal must reference the renewal_failure template key on the Stripe-failure path.',
        );
        $this->assertStringContainsString(
            "'membership_expired'",
            $source,
            'ChargeRenewal must reference the membership_expired template key on the expiration path.',
        );
    }

    public function test_command_has_documented_signature_and_description(): void
    {
        $reflection = new ReflectionClass(ChargeRenewal::class);
        $defaults = $reflection->getDefaultProperties();

        $this->assertSame('charge-renewal', $defaults['signature']);
        $this->assertSame(
            'Attempts to charge all recurring renewals due today.',
            $defaults['description'],
        );
    }

    /* ----------------------------------------------------------------
     * Behavioural tests below. Each builds a subscription stub via
     * stdClass (cheap, no Eloquent), wires up mocks for the contracts +
     * Mailer, and drives `handle()` end-to-end.
     * ---------------------------------------------------------------- */

    public function test_handle_charges_stripe_subscription_and_dispatches_renewal_receipt(): void
    {
        $subscription = $this->buildRecurringStripeSubscription();

        $paymentService = Mockery::mock(StripePaymentServiceContract::class);
        $paymentService->shouldReceive('createPayment')
            ->once()
            ->with(
                $subscription->subscriber,
                $subscription->paymentMethod,
                Mockery::pattern('/^Subscription renewal for Pro Plan$/'),
                Mockery::on(function (array $lineItems) use ($subscription) {
                    return count($lineItems) === 1
                        && $lineItems[0]['item_id'] === $subscription->id
                        && $lineItems[0]['item_type'] === 'subscription'
                        && $lineItems[0]['amount'] === 99.0;
                }),
            )
            ->andReturn(new PaymentFixture);

        $subscriptionRepo = Mockery::mock(SubscriptionRepositoryContract::class);
        $subscriptionRepo->shouldReceive('findExpiring')
            ->once()
            ->andReturn(new EloquentCollection([$subscription]));
        $subscriptionRepo->shouldReceive('update')
            ->once()
            ->with(
                $subscription,
                Mockery::on(function (array $data): bool {
                    return array_key_exists('last_renewed_at', $data)
                        && $data['last_renewed_at'] instanceof Carbon
                        && array_key_exists('expires_at', $data)
                        && $data['expires_at'] instanceof Carbon
                        && $data['is_trial'] === false;
                }),
            )
            ->andReturn($this->buildUpdatedSubscription($subscription));

        $pending = Mockery::mock(PendingMail::class);
        $pending->shouldReceive('send')
            ->once()
            ->withArgs(function (TemplatedMailable $m): bool {
                return $m->templateKey === 'renewal_receipt'
                    && $m->variables['user']['first_name'] === 'Ada'
                    && $m->variables['user']['last_name'] === 'Lovelace'
                    && $m->variables['app']['name'] === 'TestApp'
                    && $m->variables['membership_name'] === 'Pro Plan';
            });

        $mailer = Mockery::mock(Mailer::class);
        $mailer->shouldReceive('to')->once()->with('ada@example.com')->andReturn($pending);

        $config = $this->configMock('TestApp');

        $command = new ChargeRenewal($paymentService, $subscriptionRepo, $mailer, $config);
        $this->assertSame(Command::SUCCESS, $command->handle());
    }

    public function test_handle_stripe_card_error_dispatches_renewal_failure_template(): void
    {
        $subscription = $this->buildRecurringStripeSubscription();

        $paymentService = Mockery::mock(StripePaymentServiceContract::class);
        $paymentService->shouldReceive('createPayment')
            ->once()
            ->andThrow(new CardErrorException('Your card was declined'));

        $subscriptionRepo = Mockery::mock(SubscriptionRepositoryContract::class);
        $subscriptionRepo->shouldReceive('findExpiring')
            ->once()
            ->andReturn(new EloquentCollection([$subscription]));
        $subscriptionRepo->shouldNotReceive('update');

        $pending = Mockery::mock(PendingMail::class);
        $pending->shouldReceive('send')
            ->once()
            ->withArgs(function (TemplatedMailable $m): bool {
                return $m->templateKey === 'renewal_failure'
                    && $m->variables['failure_reason'] === 'Your card was declined.'
                    && $m->variables['membership_name'] === 'Pro Plan';
            });

        $mailer = Mockery::mock(Mailer::class);
        $mailer->shouldReceive('to')->once()->with('ada@example.com')->andReturn($pending);

        $config = $this->configMock('TestApp');

        $command = new ChargeRenewal($paymentService, $subscriptionRepo, $mailer, $config);
        $this->assertSame(Command::SUCCESS, $command->handle());
    }

    public function test_handle_stripe_not_found_uses_card_no_longer_on_file_message(): void
    {
        $subscription = $this->buildRecurringStripeSubscription();

        $paymentService = Mockery::mock(StripePaymentServiceContract::class);
        $paymentService->shouldReceive('createPayment')
            ->once()
            ->andThrow(new NotFoundException('Stripe customer missing'));

        $subscriptionRepo = Mockery::mock(SubscriptionRepositoryContract::class);
        $subscriptionRepo->shouldReceive('findExpiring')
            ->once()
            ->andReturn(new EloquentCollection([$subscription]));
        $subscriptionRepo->shouldNotReceive('update');

        $pending = Mockery::mock(PendingMail::class);
        $pending->shouldReceive('send')
            ->once()
            ->withArgs(function (TemplatedMailable $m): bool {
                return $m->templateKey === 'renewal_failure'
                    && $m->variables['failure_reason'] === 'Renewal card no longer on file.';
            });

        $mailer = Mockery::mock(Mailer::class);
        $mailer->shouldReceive('to')->once()->with('ada@example.com')->andReturn($pending);

        $command = new ChargeRenewal(
            $paymentService,
            $subscriptionRepo,
            $mailer,
            $this->configMock('TestApp'),
        );
        $this->assertSame(Command::SUCCESS, $command->handle());
    }

    public function test_handle_non_recurring_subscription_sends_expiration_email(): void
    {
        $subscription = $this->buildNonRecurringSubscription();

        $paymentService = Mockery::mock(StripePaymentServiceContract::class);
        $paymentService->shouldNotReceive('createPayment');

        $subscriptionRepo = Mockery::mock(SubscriptionRepositoryContract::class);
        $subscriptionRepo->shouldReceive('findExpiring')
            ->once()
            ->andReturn(new EloquentCollection([$subscription]));
        $subscriptionRepo->shouldNotReceive('update');

        $pending = Mockery::mock(PendingMail::class);
        $pending->shouldReceive('send')
            ->once()
            ->withArgs(function (TemplatedMailable $m): bool {
                return $m->templateKey === 'membership_expired'
                    && $m->variables['membership_name'] === 'Basic Plan';
            });

        $mailer = Mockery::mock(Mailer::class);
        $mailer->shouldReceive('to')->once()->with('ada@example.com')->andReturn($pending);

        $command = new ChargeRenewal(
            $paymentService,
            $subscriptionRepo,
            $mailer,
            $this->configMock('TestApp'),
        );
        $this->assertSame(Command::SUCCESS, $command->handle());
    }

    public function test_handle_skips_subscription_when_subscriber_already_has_renewed_membership(): void
    {
        $subscription = $this->buildRecurringStripeSubscription();
        // Simulate the subscriber already having a current subscription
        // for tomorrow (the early-skip branch in handle()).
        $subscription->subscriber
            ->shouldReceive('currentSubscription')
            ->andReturn(new SubscriptionFixture);

        $paymentService = Mockery::mock(StripePaymentServiceContract::class);
        $paymentService->shouldNotReceive('createPayment');

        $subscriptionRepo = Mockery::mock(SubscriptionRepositoryContract::class);
        $subscriptionRepo->shouldReceive('findExpiring')
            ->once()
            ->andReturn(new EloquentCollection([$subscription]));
        $subscriptionRepo->shouldNotReceive('update');

        $mailer = Mockery::mock(Mailer::class);
        $mailer->shouldNotReceive('to');

        $command = new ChargeRenewal(
            $paymentService,
            $subscriptionRepo,
            $mailer,
            $this->configMock('TestApp'),
        );
        $this->assertSame(Command::SUCCESS, $command->handle());
    }

    public function test_handle_recurring_paypal_subscription_is_no_op_check_paypal(): void
    {
        $subscription = $this->buildRecurringStripeSubscription();
        $subscription->paymentMethod->payment_method_type = 'paypal';

        $paymentService = Mockery::mock(StripePaymentServiceContract::class);
        $paymentService->shouldNotReceive('createPayment');

        $subscriptionRepo = Mockery::mock(SubscriptionRepositoryContract::class);
        $subscriptionRepo->shouldReceive('findExpiring')
            ->once()
            ->andReturn(new EloquentCollection([$subscription]));
        $subscriptionRepo->shouldNotReceive('update');

        $mailer = Mockery::mock(Mailer::class);
        $mailer->shouldNotReceive('to');

        $command = new ChargeRenewal(
            $paymentService,
            $subscriptionRepo,
            $mailer,
            $this->configMock('TestApp'),
        );
        $this->assertSame(Command::SUCCESS, $command->handle());
    }

    public function test_handle_non_user_subscriber_type_is_silently_skipped_on_expiration(): void
    {
        $subscription = $this->buildNonRecurringSubscription();
        // sendExpirationEmail() (and sendFailureEmail()) short-circuit for
        // non-`user` subscriber types — see the early `subscriber_type !==
        // 'user'` guard at the top of each method.
        $subscription->subscriber_type = 'organization';

        $paymentService = Mockery::mock(StripePaymentServiceContract::class);

        $subscriptionRepo = Mockery::mock(SubscriptionRepositoryContract::class);
        $subscriptionRepo->shouldReceive('findExpiring')
            ->once()
            ->andReturn(new EloquentCollection([$subscription]));

        $mailer = Mockery::mock(Mailer::class);
        $mailer->shouldNotReceive('to');

        $command = new ChargeRenewal(
            $paymentService,
            $subscriptionRepo,
            $mailer,
            $this->configMock('TestApp'),
        );
        $this->assertSame(Command::SUCCESS, $command->handle());
    }

    /* --------------------------- helpers ----------------------------- */

    /**
     * Builds a fixture-backed subscription stub that exposes every
     * property + nested object ChargeRenewal touches. We instantiate the
     * SubscriptionFixture (aliased to App\Models\Subscription\Subscription)
     * so the command's `Subscription $subscription` type hint passes; for
     * everything else we lean on stdClass / a small anonymous wrapper to
     * stay out of Eloquent's __set/setAttribute path. Avoids needing the
     * consumer-app's BaseModelAbstract (which itself depends on AdminUI
     * EloquentJoin — not in this package's composer.json).
     */
    private function buildRecurringStripeSubscription(): SubscriptionFixture
    {
        // Subscriber needs to satisfy two type hints:
        //   - createPayment($entity) expects IsAnEntityContract
        //   - Mailer::to($address) only needs ->email
        // Build a Mockery mock of the User fixture that also implements
        // the entity contract; expose `currentSubscription()` so the
        // early-skip branch in handle() resolves to null by default.
        $subscriber = Mockery::mock(UserFixture::class, IsAnEntityContract::class);
        $subscriber->shouldReceive('currentSubscription')->andReturnNull()->byDefault();
        $subscriber->email = 'ada@example.com';
        $subscriber->first_name = 'Ada';
        $subscriber->last_name = 'Lovelace';

        $plan = new stdClass;
        $plan->name = 'Pro Plan';

        $rate = new stdClass;
        $rate->cost = 99.0;
        $rate->membershipPlan = $plan;

        $paymentMethod = new PaymentMethodFixture;
        $paymentMethod->payment_method_type = 'stripe';

        $subscription = new SubscriptionFixture;
        $subscription->id = 42;
        $subscription->recurring = true;
        $subscription->subscriber_type = 'user';
        $subscription->subscriber = $subscriber;
        $subscription->paymentMethod = $paymentMethod;
        $subscription->membershipPlanRate = $rate;
        $subscription->formatted_cost = '99.00';
        $subscription->formatted_expires_at = 'July 1st 2027';

        return $subscription;
    }

    private function buildNonRecurringSubscription(): SubscriptionFixture
    {
        $subscription = $this->buildRecurringStripeSubscription();
        $subscription->recurring = false;
        $subscription->membershipPlanRate->membershipPlan->name = 'Basic Plan';

        return $subscription;
    }

    /**
     * Mirrors the post-update subscription returned by
     * SubscriptionRepository::update() — used by handleSuccess() to fan
     * out variables into the TemplatedMailable.
     */
    private function buildUpdatedSubscription(SubscriptionFixture $subscription): SubscriptionFixture
    {
        // Reuse the same graph; handleSuccess() only reads
        // ->subscriber, ->membershipPlanRate->membershipPlan, etc.
        return $subscription;
    }

    private function configMock(string $appName): Repository
    {
        $config = Mockery::mock(Repository::class);
        $config->shouldReceive('get')->with('app.name', 'Polis')->andReturn($appName);

        return $config;
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
