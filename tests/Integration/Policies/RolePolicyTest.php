<?php

declare(strict_types=1);

namespace Polis\Tests\Integration\Policies;

use App\Models\User\User;
use App\Policies\RolePolicy;
use Polis\Tests\Application\ApplicationTestCase;

/**
 * Class RolePolicyTest
 */
final class RolePolicyTest extends ApplicationTestCase
{
    public function test_all(): void
    {
        $policy = new RolePolicy;

        $this->assertFalse($policy->all(new User));
    }
}
