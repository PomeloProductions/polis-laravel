<?php

declare(strict_types=1);

namespace Polis\Listeners\Organization;

use Illuminate\Contracts\Mail\Mailer;
use Polis\Events\Organization\OrganizationManagerCreatedEvent;
use Polis\Mail\TemplatedMailable;

/**
 * Class OrganizationManagerCreatedListener
 *
 * Notifies a user that they have been granted manager access to an
 * organization, including their temporary password if one was generated.
 * Migrated from PolisOS's app/Listeners/Organization/OrganizationManagerCreatedListener.php.
 *
 * Template key: `organization_manager_added`.
 * Per-organization template overrides apply automatically — the listener
 * passes the organization id through so an org-scoped row in
 * email_templates (organization_id = $organization->id) wins over the
 * global default.
 */
class OrganizationManagerCreatedListener
{
    public function __construct(
        private readonly Mailer $mailer,
    ) {}

    public function handle(OrganizationManagerCreatedEvent $event): void
    {
        $organizationManager = $event->getOrganizationManager();
        $tempPassword = $event->getTempPassword();
        $organization = $organizationManager->organization;
        $user = $organizationManager->user;

        $variables = [
            'user' => [
                'first_name' => $user->first_name ?? '',
                'last_name' => $user->last_name ?? '',
                'email' => $user->email ?? '',
            ],
            'organization' => [
                'name' => $organization->name ?? '',
                'id' => $organization->id ?? null,
            ],
            'organization_role' => $organizationManager->role->name ?? '',
            'temp_password' => $tempPassword ?? '',
        ];

        $this->mailer->to($user->email)->send(new TemplatedMailable(
            templateKey: 'organization_manager_added',
            variables: $variables,
            organizationId: $organization->id ?? null,
        ));
    }
}
