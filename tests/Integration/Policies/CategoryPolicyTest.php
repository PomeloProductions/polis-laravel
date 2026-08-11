<?php

declare(strict_types=1);

namespace Polis\Tests\Integration\Policies;

use App\Models\Category;
use App\Models\User\User;
use App\Policies\CategoryPolicy;
use Polis\Tests\Application\ApplicationTestCase;

/**
 * Class CategoryPolicyTest
 */
final class CategoryPolicyTest extends ApplicationTestCase
{
    public function test_create(): void
    {
        $policy = new CategoryPolicy;
        $this->assertTrue($policy->create(new User));
    }

    public function test_update(): void
    {
        $policy = new CategoryPolicy;
        $this->assertFalse($policy->update(new User, new Category));
    }

    public function test_delete(): void
    {
        $policy = new CategoryPolicy;
        $this->assertFalse($policy->delete(new User, new Category));
    }
}
