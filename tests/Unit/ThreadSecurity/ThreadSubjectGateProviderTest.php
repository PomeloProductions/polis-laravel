<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\ThreadSecurity;

use Illuminate\Contracts\Foundation\Application;
use Polis\Tests\TestCase;
use Polis\ThreadSecurity\GeneralThreadGate;
use Polis\ThreadSecurity\PrivateThreadGate;
use Polis\ThreadSecurity\ThreadSubjectGateProvider;

/**
 * Class ThreadSubjectGateProviderTest
 */
final class ThreadSubjectGateProviderTest extends TestCase
{
    public function test_create_gate(): void
    {
        $provider = new ThreadSubjectGateProvider(mock(Application::class));

        $result = $provider->createGate('general');
        $this->assertInstanceOf(GeneralThreadGate::class, $result);

        $result = $provider->createGate('private_message');
        $this->assertInstanceOf(PrivateThreadGate::class, $result);

        $result = $provider->createGate('rioth');
        $this->assertNull($result);
    }
}
