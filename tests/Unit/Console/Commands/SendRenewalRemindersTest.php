<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Database\Eloquent\Collection;
use Polis\Console\Commands\SendRenewalReminders;
use Polis\Contracts\Repositories\Subscription\SubscriptionRepositoryContract;
use Polis\Tests\TestCase;
use ReflectionClass;
use ReflectionNamedType;

/**
 * Verifies the SendRenewalReminders command class shape — it returns
 * SUCCESS when there are no expiring subscriptions and depends only on
 * Polis-namespaced contracts plus framework abstractions.
 *
 * Deeper behavioural coverage of the per-subscription mailer dispatch
 * requires App\Models\Subscription\* fakes and lives in Consumer-Only.
 */
final class SendRenewalRemindersTest extends TestCase
{
    public function test_command_exists_in_polis_namespace(): void
    {
        $this->assertTrue(class_exists(SendRenewalReminders::class));
        $this->assertTrue(is_subclass_of(SendRenewalReminders::class, Command::class));
    }

    public function test_constructor_only_depends_on_polis_contracts_and_framework_abstractions(): void
    {
        $constructor = (new ReflectionClass(SendRenewalReminders::class))->getConstructor();
        $this->assertNotNull($constructor);

        $expectedTypes = [
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

    public function test_handle_returns_success_when_no_expiring_subscriptions(): void
    {
        $repo = mock(SubscriptionRepositoryContract::class);
        $repo->shouldReceive('findExpiring')->once()->andReturn(new Collection);

        $mailer = mock(Mailer::class);
        // No mailer call expected: no expiring subscriptions.

        $config = mock(Repository::class);

        $command = new SendRenewalReminders($repo, $mailer, $config);

        $this->assertSame(Command::SUCCESS, $command->handle());
    }
}
