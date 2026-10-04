<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\Testing;

use App\Models\Role;
use App\Models\User\User;
use Polis\Testing\RolesTesting;
use Polis\Tests\Application\ApplicationTestCase;
use Polis\Tests\Traits\MocksApplicationLog;

/**
 * Proves the shipped testable-authorization pattern (src/Testing/*) lets a
 * consumer assert 401/403/ownership against the REAL policy layer, even though
 * the JWT middleware no-ops under the `testing` environment.
 *
 * This test deliberately imports the helpers from the PUBLISHED namespace
 * (`Polis\Testing\RolesTesting`, and `Polis\Testing\AuthorizesWithGuard` /
 * `SeedsRoles` via ApplicationTestCase) — i.e. exactly what a consuming app
 * receives from the package — so it doubles as the executable reference a
 * consumer copies to burn down its `markTestIncomplete` 403 tests.
 *
 * The target endpoint is `PATCH /v1/users/{id}`, guarded by
 * `UserPolicyAbstract::update()` which only permits `$user->id == $model->id`.
 */
final class ShippedTestableAuthTest extends ApplicationTestCase
{
    use MocksApplicationLog;
    use RolesTesting;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockApplicationLog();
    }

    /**
     * No actAs* call => no authenticated user => the policy denies =>
     * AuthorizationException => 403. This is the "not logged in" assertion that
     * was previously impossible to exercise.
     */
    public function test_not_logged_in_update_is_forbidden(): void
    {
        $target = User::factory()->create();

        $this->json('PATCH', '/v1/users/'.$target->id, ['first_name' => 'Nope'])
            ->assertStatus(403);
    }

    /**
     * Authenticated as a different user => ownership check fails => 403.
     */
    public function test_updating_another_user_is_forbidden(): void
    {
        $this->actAs(Role::APP_USER);              // sets $this->actingAs
        $target = User::factory()->create();       // a DIFFERENT user

        $this->json('PATCH', '/v1/users/'.$target->id, ['first_name' => 'Nope'])
            ->assertStatus(403);
    }

    /**
     * Authenticated as the owner => the policy permits => the request runs.
     */
    public function test_updating_self_is_allowed(): void
    {
        $this->actAs(Role::APP_USER);

        $this->json('PATCH', '/v1/users/'.$this->actingAs->id, ['first_name' => 'Yep'])
            ->assertStatus(200);
    }
}
