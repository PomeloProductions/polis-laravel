<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Listeners\Organization;

use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Mail\PendingMail;
use Mockery;
use Polis\Contracts\Services\InvitationUrlServiceContract;
use Polis\Events\Organization\OrganizationManagerCreatedEvent;
use Polis\Listeners\Organization\OrganizationManagerCreatedListener;
use Polis\Mail\TemplatedMailable;
use Polis\Tests\Fixtures\Models\InvitationToken as InvitationTokenFixture;
use Polis\Tests\TestCase;

/**
 * Standalone coverage for the Polis-namespaced
 * OrganizationManagerCreatedListener — the existing
 * OrganizationManagerCreatedListenerTest.php imports App\Listeners
 * (consumer-app) and lives in Consumer-Only.
 *
 * Covers both dispatch paths:
 *   - temp-password (legacy) -> `organization_manager_added`
 *   - invitation token       -> `organization_manager_invited` + accept_url
 * Both pass the organizationId through to enable per-org template overrides.
 */
final class OrganizationManagerCreatedListenerStandaloneTest extends TestCase
{
    public function test_handle_sends_templated_email_with_organization_id_for_org_scoped_lookup(): void
    {
        // Use a real fixture User instance (which now extends
        // BaseModelAbstract) — Mockery doubles of Eloquent models require
        // stubbing the ArrayAccess methods triggered by `??`, and a real
        // instance handles all of that automatically.
        $userClass = 'App\\Models\\User\\User';
        $user = new $userClass;
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

        $urlService = Mockery::mock(InvitationUrlServiceContract::class);
        $urlService->shouldNotReceive('buildAcceptUrl');

        $listener = new OrganizationManagerCreatedListener($mailer, $urlService);
        $event = new OrganizationManagerCreatedEvent($orgManager, 'temp123');
        $listener->handle($event);
    }

    public function test_handle_sends_invitation_email_with_accept_url_when_token_present(): void
    {
        $userClass = 'App\\Models\\User\\User';
        $user = new $userClass;
        $user->first_name = 'Sue';
        $user->last_name = 'Storm';
        $user->email = 'sue@org.test';

        $organization = Mockery::mock('App\\Models\\Organization\\Organization');
        $organization->name = 'Acme';
        $organization->id = 42;

        $role = Mockery::mock('App\\Models\\Role');
        $role->name = 'Administrator';

        $orgManager = Mockery::mock('App\\Models\\Organization\\OrganizationManager');
        $orgManager->user = $user;
        $orgManager->organization = $organization;
        $orgManager->role = $role;

        $invitationToken = new InvitationTokenFixture;
        $invitationToken->token = 'tok-abc';

        $urlService = Mockery::mock(InvitationUrlServiceContract::class);
        $urlService->shouldReceive('buildAcceptUrl')
            ->once()
            ->with('tok-abc')
            ->andReturn('https://app.example.com/accept-invitation?invitation_token=tok-abc');

        $pending = Mockery::mock(PendingMail::class);
        $pending->shouldReceive('send')->once()->withArgs(
            fn (TemplatedMailable $m) => $m->templateKey === 'organization_manager_invited'
                && $m->organizationId === 42
                && $m->variables['user']['first_name'] === 'Sue'
                && $m->variables['organization']['name'] === 'Acme'
                && $m->variables['organization_role'] === 'Administrator'
                && $m->variables['accept_url'] === 'https://app.example.com/accept-invitation?invitation_token=tok-abc'
                && $m->variables['inviter']['name'] === 'Nick Fury'
        );

        $mailer = Mockery::mock(Mailer::class);
        $mailer->shouldReceive('to')->once()->with('sue@org.test')->andReturn($pending);

        $listener = new OrganizationManagerCreatedListener($mailer, $urlService);
        $event = new OrganizationManagerCreatedEvent($orgManager, null, $invitationToken, 'Nick Fury');
        $listener->handle($event);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
