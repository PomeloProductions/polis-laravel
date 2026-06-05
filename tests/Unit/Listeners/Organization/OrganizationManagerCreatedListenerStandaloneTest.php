<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Listeners\Organization;

use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Mail\PendingMail;
use Mockery;
use Polis\Events\Organization\OrganizationManagerCreatedEvent;
use Polis\Listeners\Organization\OrganizationManagerCreatedListener;
use Polis\Mail\TemplatedMailable;
use Polis\Tests\TestCase;

/**
 * Standalone coverage for the Polis-namespaced
 * OrganizationManagerCreatedListener — the existing
 * OrganizationManagerCreatedListenerTest.php imports App\Listeners
 * (consumer-app) and lives in Consumer-Only.
 *
 * Verifies the TemplatedMailable("organization_manager_added") dispatch
 * with the org-scoped lookup hint (organizationId passed through to
 * enable per-org template overrides).
 */
final class OrganizationManagerCreatedListenerStandaloneTest extends TestCase
{
    public function test_handle_sends_templated_email_with_organization_id_for_org_scoped_lookup(): void
    {
        $user = Mockery::mock('App\\Models\\User\\User');
        $user->first_name = 'Bob';
        $user->last_name = 'Builder';
        $user->email = 'bob@org.test';

        $organization = Mockery::mock('App\\Models\\Organization\\Organization');
        $organization->name = 'Acme';
        $organization->id = 42;

        $role = Mockery::mock('App\\Models\\Role');
        $role->name = 'Manager';

        $orgManager = Mockery::mock('App\\Models\\Organization\\OrganizationManager');
        $orgManager->user = $user;
        $orgManager->organization = $organization;
        $orgManager->role = $role;

        $pending = Mockery::mock(PendingMail::class);
        $pending->shouldReceive('send')->once()->withArgs(
            fn (TemplatedMailable $m) => $m->templateKey === 'organization_manager_added'
                && $m->organizationId === 42
                && $m->variables['user']['first_name'] === 'Bob'
                && $m->variables['organization']['name'] === 'Acme'
                && $m->variables['organization']['id'] === 42
                && $m->variables['organization_role'] === 'Manager'
                && $m->variables['temp_password'] === 'temp123'
        );

        $mailer = Mockery::mock(Mailer::class);
        $mailer->shouldReceive('to')->once()->with('bob@org.test')->andReturn($pending);

        $listener = new OrganizationManagerCreatedListener($mailer);
        $event = new OrganizationManagerCreatedEvent($orgManager, 'temp123');
        $listener->handle($event);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
