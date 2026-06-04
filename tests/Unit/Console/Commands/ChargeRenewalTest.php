<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Mail\Mailer;
use Polis\Console\Commands\ChargeRenewal;
use Polis\Contracts\Repositories\Messaging\MessageRepositoryContract;
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
 * (3) the success path imports TemplatedMailable so the renewal_receipt
 * template can be dispatched, and (4) the command exposes the documented
 * console signature/description constants.
 *
 * Deeper behavioural coverage (Stripe success → renewal_receipt dispatch,
 * failure → MessageRepository fallback, exit codes) requires fakes for the
 * App\Models\Subscription\* graph and is a follow-up — those models live in
 * the consumer application. The pre-existing rich integration test at
 * tests/Integration/Console/Commands/ChargeRenewalTest.php still exercises
 * the old (App-namespaced) command inside PolisOS's Consumer-Only suite;
 * migrating it to the new Polis-namespaced class is a follow-up PR.
 *
 * Why no direct `new ChargeRenewal(...)` smoke: MessageRepositoryContract
 * and StripePaymentServiceContract both reference App\Models\* types in
 * their method signatures, which prevents Mockery from generating proxies
 * inside this package's standalone (no consumer-app) Testbench harness.
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
            MessageRepositoryContract::class,
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

    public function test_command_imports_templated_mailable_for_renewal_receipt_dispatch(): void
    {
        $source = file_get_contents(
            (new ReflectionClass(ChargeRenewal::class))->getFileName(),
        );

        $this->assertStringContainsString(
            'use '.TemplatedMailable::class.';',
            $source,
            'ChargeRenewal must import TemplatedMailable to dispatch the renewal_receipt template.',
        );
        $this->assertStringContainsString(
            "'renewal_receipt'",
            $source,
            'ChargeRenewal must reference the renewal_receipt template key on the success path.',
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
