<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Mail\Mailer;
use Polis\Console\Commands\ChargeRenewal;
use Polis\Contracts\Repositories\Subscription\SubscriptionRepositoryContract;
use Polis\Contracts\Services\StripePaymentServiceContract;
use Polis\Mail\TemplatedMailable;
use Polis\Tests\TestCase;
use ReflectionClass;
use ReflectionNamedType;

/**
 * Class ChargeRenewalTest
 *
 * Minimal Unit coverage for the migrated ChargeRenewal command. We verify
 * (1) the class lives in the polis-laravel namespace and extends the
 * Laravel Console Command base, (2) it depends only on Polis-namespaced
 * contracts plus framework abstractions (no `App\*` constructor types),
 * (3) all three email paths reference TemplatedMailable and the matching
 * template keys (renewal_receipt, renewal_failure, membership_expired),
 * and (4) the command exposes the documented signature/description.
 *
 * Deeper behavioural coverage (Stripe success → renewal_receipt dispatch,
 * failure → renewal_failure dispatch, expiration → membership_expired
 * dispatch, exit codes) requires fakes for the App\Models\Subscription\*
 * graph and is a follow-up — those models live in the consumer
 * application. The pre-existing rich integration test at
 * tests/Integration/Console/Commands/ChargeRenewalTest.php still exercises
 * the old (App-namespaced) command inside PolisOS's Consumer-Only suite;
 * migrating it to the new Polis-namespaced class is a follow-up PR.
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
}
