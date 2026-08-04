<?php

declare(strict_types=1);

namespace Polis\Listeners\Organization;

use Illuminate\Contracts\Mail\Mailer;
use Polis\Contracts\Services\InvitationUrlServiceContract;
use Polis\Events\Organization\OrganizationManagerCreatedEvent;
use Polis\Mail\TemplatedMailable;

/**
 * Class OrganizationManagerCreatedListener
 *
 * Notifies a user that they have been granted manager access to an
 * organization. Chooses one of two templates depending on how the event
 * was fired:
 *
 *   - `organization_manager_invited` — a brand-new invitee. An invitation
 *     token was minted; the email carries an accept link (built by
 *     InvitationUrlService) where they set a password to activate.
 *
 *   - `organization_manager_added` — legacy temp-password path (a temporary
 *     password was generated and is included in the email), or an already
 *     existing user who simply gets a notice with no credentials.
 *
 * Migrated from PolisOS's app/Listeners/Organization/OrganizationManagerCreatedListener.php.
 *
 * Per-organization template overrides apply automatically — the listener
 * passes the organization id through so an org-scoped row in
 * email_templates (organization_id = $organization->id) wins over the
 * global default.
 */
class OrganizationManagerCreatedListener
{
    public function __construct(
        private readonly Mailer $mailer,
        private readonly InvitationUrlServiceContract $invitationUrlService,
    ) {}

    public function handle(OrganizationManagerCreatedEvent $event): void
    {
        $organizationManager = $event->getOrganizationManager();
        $tempPassword = $event->getTempPassword();
        $invitationToken = $event->getInvitationToken();
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

        if ($invitationToken !== null) {
            $variables['accept_url'] = $this->invitationUrlService->buildAcceptUrl($invitationToken->token);
            $variables['inviter'] = [
                'name' => $event->getInviterName() ?? ($organization->name ?? ''),
            ];
            $templateKey = 'organization_manager_invited';
        } else {
            $templateKey = 'organization_manager_added';
        }

        $this->mailer->to($user->email)->send(new TemplatedMailable(
            templateKey: $templateKey,
            variables: $variables,
            organizationId: $organization->id ?? null,
        ));
    }
}
