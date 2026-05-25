<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Models\Organization;

use App\Models\Organization\OrganizationManager;
use Polis\Tests\TestCase;

/**
 * Class OrganizationManagerTest
 */
final class OrganizationManagerTest extends TestCase
{
    public function test_organization(): void
    {
        $message = new OrganizationManager;
        $relation = $message->organization();

        $this->assertEquals('organizations.id', $relation->getQualifiedOwnerKeyName());
        $this->assertEquals('organization_managers.organization_id', $relation->getQualifiedForeignKeyName());
    }

    public function test_role(): void
    {
        $message = new OrganizationManager;
        $relation = $message->role();

        $this->assertEquals('roles.id', $relation->getQualifiedOwnerKeyName());
        $this->assertEquals('organization_managers.role_id', $relation->getQualifiedForeignKeyName());
    }

    public function test_user(): void
    {
        $message = new OrganizationManager;
        $relation = $message->user();

        $this->assertEquals('users.id', $relation->getQualifiedOwnerKeyName());
        $this->assertEquals('organization_managers.user_id', $relation->getQualifiedForeignKeyName());
    }
}
