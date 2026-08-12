<?php

declare(strict_types=1);

namespace Polis\Tests\Integration\Policies;

use App\Models\Role;
use Polis\Tests\Mocks\BasePolicy;
use Polis\Tests\Application\ApplicationTestCase;
use Polis\Tests\Traits\RolesTesting;

/**
 * Class BasePolicyAbstractTest
 */
final class BasePolicyAbstractTest extends ApplicationTestCase
{
    use RolesTesting;

    public function test_before(): void
    {
        $policy = new BasePolicy;

        $this->assertNull($policy->before($this->getUserOfRole(Role::APP_USER)));

        $this->assertTrue($policy->before($this->getUserOfRole(Role::SUPER_ADMIN)));
    }
}
