<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Services;

use Polis\Services\InvitationUrlService;
use Polis\Tests\TestCase;

/**
 * Standalone coverage for InvitationUrlService — the configurable builder of
 * accept-invitation links embedded in invite emails. Nothing here should be
 * tied to a specific app domain; the base is always injected.
 */
final class InvitationUrlServiceTest extends TestCase
{
    public function test_uses_explicit_accept_url_base_when_provided(): void
    {
        $service = new InvitationUrlService(
            acceptUrlBase: 'https://app.example.com/accept-invitation',
            appUrl: 'http://localhost',
            fallbackPath: '/accept-invitation',
            tokenParam: 'invitation_token',
        );

        $this->assertSame(
            'https://app.example.com/accept-invitation?invitation_token=abc123',
            $service->buildAcceptUrl('abc123'),
        );
    }

    public function test_trims_trailing_slash_on_base(): void
    {
        $service = new InvitationUrlService(
            acceptUrlBase: 'https://app.example.com/accept/',
            appUrl: 'http://localhost',
            fallbackPath: '/accept-invitation',
            tokenParam: 'invitation_token',
        );

        $this->assertSame(
            'https://app.example.com/accept?invitation_token=abc123',
            $service->buildAcceptUrl('abc123'),
        );
    }

    public function test_falls_back_to_app_url_plus_path_when_base_is_null(): void
    {
        $service = new InvitationUrlService(
            acceptUrlBase: null,
            appUrl: 'https://polis.test/',
            fallbackPath: 'accept-invitation',
            tokenParam: 'invitation_token',
        );

        $this->assertSame(
            'https://polis.test/accept-invitation?invitation_token=xyz',
            $service->buildAcceptUrl('xyz'),
        );
    }

    public function test_falls_back_when_base_is_empty_string(): void
    {
        $service = new InvitationUrlService(
            acceptUrlBase: '',
            appUrl: 'https://polis.test',
            fallbackPath: '/accept',
            tokenParam: 'invitation_token',
        );

        $this->assertSame(
            'https://polis.test/accept?invitation_token=xyz',
            $service->buildAcceptUrl('xyz'),
        );
    }

    public function test_appends_with_ampersand_when_base_already_has_query_string(): void
    {
        $service = new InvitationUrlService(
            acceptUrlBase: 'https://app.example.com/accept?ref=email',
            appUrl: 'http://localhost',
            fallbackPath: '/accept-invitation',
            tokenParam: 'invitation_token',
        );

        $this->assertSame(
            'https://app.example.com/accept?ref=email&invitation_token=abc',
            $service->buildAcceptUrl('abc'),
        );
    }

    public function test_uses_configured_token_param_and_url_encodes_values(): void
    {
        $service = new InvitationUrlService(
            acceptUrlBase: 'https://app.example.com/accept',
            appUrl: 'http://localhost',
            fallbackPath: '/accept-invitation',
            tokenParam: 'invite token',
        );

        $this->assertSame(
            'https://app.example.com/accept?invite%20token=a%2Fb%20c',
            $service->buildAcceptUrl('a/b c'),
        );
    }
}
