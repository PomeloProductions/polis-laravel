<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Policies;

use App\Models\Role;
use Mockery;
use Polis\Contracts\Models\IsAnEntityContract;
use Polis\Tests\Fixtures\Models\PaymentMethod;
use Polis\Tests\Fixtures\Policies\Payment\PaymentMethodPolicy;
use Polis\Tests\Fixtures\Policies\Payment\PaymentPolicy;
use Polis\Tests\TestCase;

/**
 * Coverage for PaymentPolicyAbstract / PaymentMethodPolicyAbstract.
 *
 * PaymentPolicy is a single-gate abstract (all -> canUserManageEntity()).
 * PaymentMethodPolicy requires ADMINISTRATOR and validates owner_type +
 * owner_id against the entity for update/delete.
 */
final class PaymentPolicyAbstractTest extends TestCase
{
    public function test_payment_all_requires_manage_entity(): void
    {
        $policy = new PaymentPolicy;
        $user = Mockery::mock('App\\Models\\User\\User');
        $entity = Mockery::mock(IsAnEntityContract::class);
        $entity->shouldReceive('canUserManageEntity')->once()->with($user)->andReturn(true);

        $this->assertTrue($policy->all($user, $entity));
    }

    public function test_payment_all_denies_when_user_cannot_manage(): void
    {
        $policy = new PaymentPolicy;
        $user = Mockery::mock('App\\Models\\User\\User');
        $entity = Mockery::mock(IsAnEntityContract::class);
        $entity->shouldReceive('canUserManageEntity')->once()->with($user)->andReturn(false);

        $this->assertFalse($policy->all($user, $entity));
    }

    public function test_payment_method_create_requires_administrator(): void
    {
        $policy = new PaymentMethodPolicy;
        $user = Mockery::mock('App\\Models\\User\\User');
        $entity = Mockery::mock(IsAnEntityContract::class);
        $entity->shouldReceive('canUserManageEntity')->once()->with($user, Role::ADMINISTRATOR)->andReturn(true);

        $this->assertTrue($policy->create($user, $entity));
    }

    public function test_payment_method_update_allows_when_admin_and_owner_matches(): void
    {
        $policy = new PaymentMethodPolicy;
        $user = Mockery::mock('App\\Models\\User\\User');
        $entity = Mockery::mock(IsAnEntityContract::class);
        $entity->id = 5;
        $entity->shouldReceive('canUserManageEntity')->once()->with($user, Role::ADMINISTRATOR)->andReturn(true);
        $entity->shouldReceive('morphRelationName')->andReturn('organization');
        $paymentMethod = new PaymentMethod;
        $paymentMethod->owner_type = 'organization';
        $paymentMethod->owner_id = 5;

        $this->assertTrue($policy->update($user, $entity, $paymentMethod));
    }

    public function test_payment_method_update_denies_when_owner_type_mismatches(): void
    {
        $policy = new PaymentMethodPolicy;
        $user = Mockery::mock('App\\Models\\User\\User');
        $entity = Mockery::mock(IsAnEntityContract::class);
        $entity->id = 5;
        $entity->shouldReceive('canUserManageEntity')->once()->with($user, Role::ADMINISTRATOR)->andReturn(true);
        $entity->shouldReceive('morphRelationName')->andReturn('organization');
        $paymentMethod = new PaymentMethod;
        $paymentMethod->owner_type = 'user';
        $paymentMethod->owner_id = 5;

        $this->assertFalse($policy->update($user, $entity, $paymentMethod));
    }

    public function test_payment_method_update_denies_when_owner_id_mismatches(): void
    {
        $policy = new PaymentMethodPolicy;
        $user = Mockery::mock('App\\Models\\User\\User');
        $entity = Mockery::mock(IsAnEntityContract::class);
        $entity->id = 5;
        $entity->shouldReceive('canUserManageEntity')->once()->with($user, Role::ADMINISTRATOR)->andReturn(true);
        $entity->shouldReceive('morphRelationName')->andReturn('organization');
        $paymentMethod = new PaymentMethod;
        $paymentMethod->owner_type = 'organization';
        $paymentMethod->owner_id = 99;

        $this->assertFalse($policy->update($user, $entity, $paymentMethod));
    }

    public function test_payment_method_delete_allows_when_admin_and_owner_matches(): void
    {
        $policy = new PaymentMethodPolicy;
        $user = Mockery::mock('App\\Models\\User\\User');
        $entity = Mockery::mock(IsAnEntityContract::class);
        $entity->id = 5;
        $entity->shouldReceive('canUserManageEntity')->once()->with($user, Role::ADMINISTRATOR)->andReturn(true);
        $entity->shouldReceive('morphRelationName')->andReturn('organization');
        $paymentMethod = new PaymentMethod;
        $paymentMethod->owner_type = 'organization';
        $paymentMethod->owner_id = 5;

        $this->assertTrue($policy->delete($user, $entity, $paymentMethod));
    }

    public function test_payment_method_delete_denies_when_not_admin(): void
    {
        $policy = new PaymentMethodPolicy;
        $user = Mockery::mock('App\\Models\\User\\User');
        $entity = Mockery::mock(IsAnEntityContract::class);
        $entity->id = 5;
        $entity->shouldReceive('canUserManageEntity')->once()->with($user, Role::ADMINISTRATOR)->andReturn(false);
        $paymentMethod = new PaymentMethod;
        $paymentMethod->owner_type = 'organization';
        $paymentMethod->owner_id = 5;

        $this->assertFalse($policy->delete($user, $entity, $paymentMethod));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
