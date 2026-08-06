<?php

declare(strict_types=1);

namespace Polis\Events\Organization;

use App\Models\Organization\OrganizationManager;
use Polis\Models\User\InvitationToken;

/**
 * Class OrganizationManagerCreatedEvent
 *
 * Fired when a user is granted manager access to an organization. Two
 * activation paths are supported and mutually exclusive:
 *
 *   - Invitation flow (preferred): the invitee is a brand-new account and an
 *     $invitationToken is generated. The invite email links them to the
 *     accept-invitation page where they set a password to activate. See
 *     OrganizationManagerCreatedListener.
 *
 *   - Temp-password flow (legacy fallback): a $tempPassword is generated and
 *     emailed directly. Retained for backward compatibility.
 *
 * When the invited user already existed, neither value is set — no
 * credential email is needed.
 */
class OrganizationManagerCreatedEvent
{
    /**
     * @var OrganizationManager
     */
    private $organizationManager;

    /**
     * @var string|null
     */
    private $tempPassword;

    private ?InvitationToken $invitationToken;

    private ?string $inviterName;

    /**
     * OrganizationManagerCreatedEvent constructor.
     *
     * @param  string|null  $inviterName  Display name of the admin who issued
     *                                    the invitation, used in the invite
     *                                    email. Optional; the listener degrades
     *                                    gracefully when absent.
     */
    public function __construct(OrganizationManager $organizationManager, ?string $tempPassword = null, ?InvitationToken $invitationToken = null, ?string $inviterName = null)
    {
        $this->organizationManager = $organizationManager;
        $this->tempPassword = $tempPassword;
        $this->invitationToken = $invitationToken;
        $this->inviterName = $inviterName;
    }

    public function getOrganizationManager(): OrganizationManager
    {
        return $this->organizationManager;
    }

    public function getTempPassword(): ?string
    {
        return $this->tempPassword;
    }

    public function getInvitationToken(): ?InvitationToken
    {
        return $this->invitationToken;
    }

    public function getInviterName(): ?string
    {
        return $this->inviterName;
    }
}
