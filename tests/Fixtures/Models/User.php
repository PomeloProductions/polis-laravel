<?php

declare(strict_types=1);

namespace Polis\Tests\Fixtures\Models;

/**
 * Fixture stub for App\Models\User\User.
 *
 * Polis contracts type-hint App\Models\* concrete classes in their method
 * signatures (e.g. MessageRepositoryContract::sendEmailToUser(User $user)).
 * In a consumer application those classes are provided by the consumer; in
 * this package's standalone Testbench harness they don't exist, which
 * blocks Mockery from proxying the contracts. This fixture provides a
 * minimal class at the expected FQCN so the type hints resolve.
 *
 * Why a plain class (no parent)?
 *   1. Polis\Models\BaseModelAbstract pulls in
 *      AdminUI\Laravel\EloquentJoin\Traits\EloquentJoin, which is a
 *      consumer-provided dependency and not in this package's composer.json.
 *   2. Extending Illuminate\Database\Eloquent\Model directly inherits a
 *      __set magic that routes property assignment through setAttribute(),
 *      which collides with Mockery mocks that need to set arbitrary dynamic
 *      properties (e.g. $user->first_name = 'Ada' inside a test).
 * Keeping the fixture parentless makes it loadable in any standalone
 * context and friendly to Mockery's dynamic property handling.
 */
class User
{
    // Intentionally empty. Used purely for type-hint satisfaction in
    // standalone tests. Add no consumer-specific logic here.
}

if (! class_exists(\App\Models\User\User::class, false)) {
    class_alias(
        User::class,
        \App\Models\User\User::class,
    );
}
