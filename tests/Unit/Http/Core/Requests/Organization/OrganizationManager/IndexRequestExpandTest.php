<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Http\Core\Requests\Organization\OrganizationManager;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Polis\Http\Core\Requests\Organization\OrganizationManager\IndexRequest;
use Polis\Tests\TestCase;

/**
 * Regression coverage for the organization-managers `expand[user]` fix.
 *
 * The dashboard lists an organization's managers with `expand[user]=*` so it
 * can render each manager's user in a single call. Before this fix the
 * request used HasNoExpands, so authorizeExpands() threw an
 * AuthorizationException (surfaced as 403) for ANY expand — including the
 * legitimate `user` relation.
 *
 * These tests exercise the request's own authorizeExpands()/allowedExpands()
 * contract directly (no consumer-app policy resolution required, so they run
 * in-package in the CI Unit suite). The full HTTP index test — asserting the
 * expanded `user` payload comes back 200 through the controller + repository —
 * lives in the Feature suite / client-driver.
 */
final class IndexRequestExpandTest extends TestCase
{
    private function requestWithExpand(array $expand): IndexRequest
    {
        $request = IndexRequest::create('/v1/organizations/1/organization-managers', 'GET', [
            'expand' => $expand,
        ]);

        // Bridge the freshly-created Symfony request into the FormRequest so
        // ->query('expand') resolves the same way it would in the pipeline.
        $formRequest = IndexRequest::createFrom($request, new IndexRequest);
        $formRequest->setContainer($this->app);

        return $formRequest;
    }

    public function test_user_relation_is_allowed(): void
    {
        $this->assertContains('user', (new IndexRequest)->allowedExpands());
    }

    public function test_expand_user_passes_expand_authorization(): void
    {
        $request = $this->requestWithExpand(['user' => '*']);

        // Should not throw — `user` is now an allowed expand.
        $request->authorizeExpands();

        $this->addToAssertionCount(1);
    }

    public function test_unknown_expand_is_still_rejected(): void
    {
        $request = $this->requestWithExpand(['secrets' => '*']);

        $this->expectException(AuthorizationException::class);

        $request->authorizeExpands();
    }
}
