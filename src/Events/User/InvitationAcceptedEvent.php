<?php

declare(strict_types=1);

namespace Polis\Events\User;

use App\Models\User\User;
use Polis\Models\User\InvitationToken;

/**
 * Class InvitationAcceptedEvent
 */
class InvitationAcceptedEvent
{
    private User $user;

    private InvitationToken $invitationToken;

    /**
     * InvitationAcceptedEvent constructor.
     */
    public function __construct(User $user, InvitationToken $invitationToken)
    {
        $this->user = $user;
        $this->invitationToken = $invitationToken;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getInvitationToken(): InvitationToken
    {
        return $this->invitationToken;
    }
}
