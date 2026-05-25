<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Models;

use App\Models\Role;
use Polis\Tests\TestCase;

/**
 * Class RoleTest
 */
final class RoleTest extends TestCase
{
    public function test_users(): void
    {
        $role = new Role;
        $relation = $role->users();

        $this->assertEquals('role_user', $relation->getTable());
        $this->assertEquals('role_user.role_id', $relation->getQualifiedForeignPivotKeyName());
        $this->assertEquals('role_user.user_id', $relation->getQualifiedRelatedPivotKeyName());
        $this->assertEquals('roles.id', $relation->getQualifiedParentKeyName());
    }
}
