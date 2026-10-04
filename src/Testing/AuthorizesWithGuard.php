<?php

declare(strict_types=1);

namespace Polis\Testing;

use App\Models\User\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\Concerns\InteractsWithAuthentication;
use Polis\Http\Core\Requests\BaseAuthenticatedRequestAbstract;
use Polis\Http\Middleware\JWTGetUserFromTokenProtectedRouteMiddleware;

/**
 * Shipped testable-authorization helper.
 *
 * This trait is the single source of truth for authenticating a user inside a
 * Feature/Integration test so that policy-driven 401/403/ownership assertions
 * can be exercised. It is published as part of the package's production
 * autoload ({@see composer.json} "Polis\\": "src/") specifically so consuming
 * applications inherit it instead of each hand-rolling their own copy.
 *
 * Why this works even though the JWT middleware no-ops under `testing`
 * ------------------------------------------------------------------
 * Both {@see JWTGetUserFromTokenProtectedRouteMiddleware}
 * and its unprotected sibling short-circuit when
 * `app()->environment() == 'testing'`: they parse no token and never call
 * `JWTAuth::authenticate()`. That is intentional — it lets the TEST own the
 * authenticated user rather than forcing every request to carry a freshly
 * minted, verifiable JWT (and prevents the middleware from clobbering the
 * acting user by re-resolving it from an absent token).
 *
 * The authenticated user is instead set directly on the guard via Laravel's
 * {@see InteractsWithAuthentication::actingAs()}.
 * Authorization still runs for real afterwards, in the FormRequest layer
 * ({@see BaseAuthenticatedRequestAbstract::authorize()}
 * -> `Gate::authorize($action, [$model, ...])`), so:
 *
 *   - no `actAs*()` call            => `auth()->user()` is null => Gate denies
 *                                      => AuthorizationException => 403
 *   - acting-as a non-owner/role    => the policy returns false => 403
 *   - acting-as the owner/role      => the policy returns true  => request runs
 *
 * (401 in this stack is reserved for the "a token was supplied but is
 *  missing/expired/invalid" JWT-exception family; an unauthenticated request
 *  that reaches a policy check is a 403, matching the package's own
 *  OrganizationViewTest.)
 *
 * @property Authenticatable|null $app
 *
 * @mixin InteractsWithAuthentication
 */
trait AuthorizesWithGuard
{
    /**
     * The user the current test is authenticated as. Populated by
     * {@see actAsUser()} / {@see actAs()} so tests can reference the acting
     * user's id (e.g. to create a model owned by them).
     *
     * @var User
     */
    protected $actingAs;

    /**
     * Authenticate as a freshly created user.
     *
     * @param  array  $data  overrides passed to the User factory
     */
    protected function actAsUser(array $data = []): void
    {
        $this->actingAs = User::factory()->create($data);
        $this->actingAs($this->actingAs);
    }

    /**
     * Authenticate as a freshly created user carrying the given role id.
     *
     * @param  int  $roleId  one of the \App\Models\Role role constants
     */
    protected function actAs(int $roleId): void
    {
        $this->actAsUser();
        $this->actingAs->addRole($roleId);
    }
}
