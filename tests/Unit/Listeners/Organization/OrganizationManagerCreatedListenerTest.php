<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Listeners\Organization;

use App\Listeners\Organization\OrganizationManagerCreatedListener;
use App\Models\Organization\Organization;
use App\Models\Organization\OrganizationManager;
use App\Models\Role;
use App\Models\User\User;
use Polis\Contracts\Repositories\Messaging\MessageRepositoryContract;
use Polis\Events\Organization\OrganizationManagerCreatedEvent;
use Polis\Tests\TestCase;

/**
 * Class OrganizationManagerCreatedListenerTest
 */
final class OrganizationManagerCreatedListenerTest extends TestCase
{
    public function test_handle(): void
    {
        $organizationManager = new OrganizationManager([
            'organization' => new Organization([
                'name' => 'An Organization',
            ]),
            'role' => new Role([
                'name' => 'A Role',
            ]),
            'user' => new User([
                'name' => 'A Person',
            ]),
        ]);

        $event = new OrganizationManagerCreatedEvent($organizationManager, 'password');

        $repository = mock(MessageRepositoryContract::class);
        $repository->shouldReceive('sendEmailToUser')->once();

        $listener = new OrganizationManagerCreatedListener($repository);
        $listener->handle($event);
    }
}
