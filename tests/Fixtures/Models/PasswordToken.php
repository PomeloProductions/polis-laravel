<?php

declare(strict_types=1);

namespace Polis\Tests\Fixtures\Models;

use Polis\Models\BaseModelAbstract;

/**
 * Fixture stub for App\Models\User\PasswordToken.
 *
 * Extends BaseModelAbstract because
 * `PasswordTokenRepositoryContract::findForUser()` returns
 * `?App\Models\User\PasswordToken` and `findOrFail()` is typed
 * `BaseModelAbstract`. Tests use Mockery doubles of this fixture; the
 * inheritance makes the type signature line up.
 */
class PasswordToken extends BaseModelAbstract
{
    use \Polis\Tests\Fixtures\Traits\MockeryFriendlyAttributesTrait;
}

if (! class_exists(\App\Models\User\PasswordToken::class, false)) {
    class_alias(
        PasswordToken::class,
        \App\Models\User\PasswordToken::class,
    );
}
