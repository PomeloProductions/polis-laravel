<?php

declare(strict_types=1);

namespace Polis\Services;

use Polis\Contracts\Services\InvitationUrlServiceContract;

/**
 * Class InvitationUrlService
 *
 * Assembles the "accept invitation" link that is embedded in invite emails.
 *
 * Resolution of the base URL (all configurable, nothing hardcoded to a
 * specific app):
 *   1. config('polis.invitations.accept_url_base') when set
 *      (env INVITATION_ACCEPT_URL_BASE), else
 *   2. rtrim(config('app.url')) + config('polis.invitations.accept_url_fallback_path')
 *
 * The token is appended under config('polis.invitations.accept_url_token_param')
 * (default `invitation_token`), preserving any query string already present
 * on the base URL.
 */
class InvitationUrlService implements InvitationUrlServiceContract
{
    /**
     * @param  string|null  $acceptUrlBase  Fully-qualified base URL of the accept page, or null to fall back to app.url + path.
     * @param  string  $appUrl  The application URL used for the fallback.
     * @param  string  $fallbackPath  Path appended to $appUrl in the fallback case.
     * @param  string  $tokenParam  Query-string parameter name the token is passed under.
     */
    public function __construct(
        private readonly ?string $acceptUrlBase,
        private readonly string $appUrl,
        private readonly string $fallbackPath,
        private readonly string $tokenParam,
    ) {}

    /**
     * {@inheritDoc}
     */
    public function buildAcceptUrl(string $token): string
    {
        $base = $this->resolveBase();

        $separator = str_contains($base, '?') ? '&' : '?';

        return $base.$separator.rawurlencode($this->tokenParam).'='.rawurlencode($token);
    }

    /**
     * Resolves the configured base URL, falling back to app.url + path when
     * no explicit accept_url_base is configured.
     */
    private function resolveBase(): string
    {
        if ($this->acceptUrlBase !== null && $this->acceptUrlBase !== '') {
            return rtrim($this->acceptUrlBase, '/');
        }

        $path = '/'.ltrim($this->fallbackPath, '/');

        return rtrim($this->appUrl, '/').$path;
    }
}
