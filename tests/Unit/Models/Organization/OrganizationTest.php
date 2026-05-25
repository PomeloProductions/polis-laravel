<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Models\Organization;

use App\Models\Organization\Organization;
use Polis\Tests\TestCase;

/**
 * Class OrganizationTest
 */
final class OrganizationTest extends TestCase
{
    public function test_assets(): void
    {
        $user = new Organization;
        $relation = $user->assets();

        $this->assertEquals('organizations.id', $relation->getQualifiedParentKeyName());
        $this->assertEquals('assets.owner_id', $relation->getQualifiedForeignKeyName());
    }

    public function test_organization_managers(): void
    {
        $user = new Organization;
        $relation = $user->organizationManagers();

        $this->assertEquals('organizations.id', $relation->getQualifiedParentKeyName());
        $this->assertEquals('organization_managers.organization_id', $relation->getQualifiedForeignKeyName());
    }

    public function test_payments(): void
    {
        $user = new Organization;
        $relation = $user->payments();

        $this->assertEquals('organizations.id', $relation->getQualifiedParentKeyName());
        $this->assertEquals('payments.owner_id', $relation->getQualifiedForeignKeyName());
    }

    public function test_payment_methods(): void
    {
        $user = new Organization;
        $relation = $user->paymentMethods();

        $this->assertEquals('organizations.id', $relation->getQualifiedParentKeyName());
        $this->assertEquals('payment_methods.owner_id', $relation->getQualifiedForeignKeyName());
    }

    public function test_profile_image(): void
    {
        $model = new Organization;

        $relation = $model->profileImage();

        $this->assertEquals('organizations.profile_image_id', $relation->getQualifiedForeignKeyName());
        $this->assertEquals('assets.id', $relation->getQualifiedOwnerKeyName());
    }

    public function test_subscriptions(): void
    {
        $user = new Organization;
        $relation = $user->subscriptions();

        $this->assertEquals('organizations.id', $relation->getQualifiedParentKeyName());
        $this->assertEquals('subscriptions.subscriber_id', $relation->getQualifiedForeignKeyName());
    }
}
