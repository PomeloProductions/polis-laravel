<?php

declare(strict_types=1);

namespace Polis\Contracts\Services;

/**
 * Interface InvitationUrlServiceContract
 *
 * Builds the URL an invitee follows to accept an invitation and set a
 * password. The base URL is fully configurable (see config/polis.php
 * `invitations.accept_url_base`) so the package never hardcodes a single
 * app's domain.
 */
interface InvitationUrlServiceContract
{
    /**
     * Build the accept-invitation URL for the given invitation token value.
     *
     * The token is appended as the configured query parameter (default
     * `invitation_token`). Any query string already present on the base URL
     * is preserved.
     */
    public function buildAcceptUrl(string $token): string;
}
