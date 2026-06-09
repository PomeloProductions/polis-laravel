<?php

declare(strict_types=1);

namespace Polis\Tests\Fixtures\Models;

use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;
use Polis\Models\BaseModelAbstract;
use Polis\Tests\Fixtures\Traits\MockeryFriendlyAttributesTrait;

/**
 * Fixture stub for App\Models\User\User.
 *
 * Polis contracts type-hint App\Models\* concrete classes in their method
 * signatures, and many also pass User through repository methods that
 * require a BaseModelAbstract argument. This fixture extends
 * BaseModelAbstract to satisfy both constraints simultaneously.
 *
 * It uses MockeryFriendlyAttributesTrait to bypass Eloquent's
 * __set/setAttribute pipeline; see that trait's docblock for the
 * rationale. The key consequence is that listener tests in this suite
 * can do:
 *
 *     $user = Mockery::mock(\App\Models\User\User::class);
 *     $user->first_name = 'Ada';
 *
 * without strict-mode Mockery rejecting the implied setAttribute() call.
 */
class User extends BaseModelAbstract implements JWTSubject
{
    use MockeryFriendlyAttributesTrait;

    /**
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * @return array<string,mixed>
     */
    public function getJWTCustomClaims(): array
    {
        return [];
    }
}

if (! class_exists(\App\Models\User\User::class, false)) {
    class_alias(
        User::class,
        \App\Models\User\User::class,
    );
}
