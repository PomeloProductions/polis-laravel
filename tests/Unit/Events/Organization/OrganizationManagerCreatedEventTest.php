<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Events\Organization;

use App\Models\Organization\OrganizationManager;
use Polis\Events\Organization\OrganizationManagerCreatedEvent;
use Polis\Tests\TestCase;

/**
 * Class OrganizationManagerCreatedEventTest
 */
final class OrganizationManagerCreatedEventTest extends TestCase
{
    public function test_without_password(): void
    {
        $organizationManager = new OrganizationManager;
        $event = new OrganizationManagerCreatedEvent($organizationManager);

        $this->assertEquals($organizationManager, $event->getOrganizationManager());
        $this->assertNull($event->getTempPassword());
    }

    public function test_with_password(): void
    {
        $organizationManager = new OrganizationManager;
        $event = new OrganizationManagerCreatedEvent($organizationManager, 'password');

        $this->assertEquals($organizationManager, $event->getOrganizationManager());
        $this->assertEquals('password', $event->getTempPassword());
    }
}
