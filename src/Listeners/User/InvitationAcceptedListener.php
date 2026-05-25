<?php

declare(strict_types=1);

namespace Polis\Listeners\User;

use Illuminate\Support\Carbon;
use Polis\Contracts\Repositories\User\InvitationTokenRepositoryContract;
use Polis\Events\User\InvitationAcceptedEvent;

/**
 * Class InvitationAcceptedListener
 */
class InvitationAcceptedListener
{
    private InvitationTokenRepositoryContract $invitationTokenRepository;

    /**
     * InvitationAcceptedListener constructor.
     */
    public function __construct(InvitationTokenRepositoryContract $invitationTokenRepository)
    {
        $this->invitationTokenRepository = $invitationTokenRepository;
    }

    /**
     * Handles the invitation accepted event by marking the token as used
     * and adding the associated role to the user if present
     */
    public function handle(InvitationAcceptedEvent $event): void
    {
        $user = $event->getUser();
        $invitationToken = $event->getInvitationToken();

        // Mark the invitation token as used
        $this->invitationTokenRepository->update($invitationToken, [
            'used_at' => Carbon::now(),
        ]);

        // If the invitation token has a role, add it to the user
        if ($invitationToken->role_id) {
            $user->roles()->attach($invitationToken->role_id);
        }
    }
}
