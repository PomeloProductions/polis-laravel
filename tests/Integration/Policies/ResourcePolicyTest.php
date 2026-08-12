<?php

declare(strict_types=1);

namespace Polis\Tests\Integration\Policies;

use App\Models\User\User;
use App\Policies\ResourcePolicy;
use Polis\Tests\Application\ApplicationTestCase;

/**
 * Class ResourcePolicyTest
 */
final class ResourcePolicyTest extends ApplicationTestCase
{
    public function test_all(): void
    {
        $policy = new ResourcePolicy;

        $this->assertTrue($policy->all(new User));
    }
}
