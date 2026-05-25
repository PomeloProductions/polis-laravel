<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Models;

use App\Models\Feature;
use Polis\Tests\TestCase;

/**
 * Class FeatureTest
 */
final class FeatureTest extends TestCase
{
    public function test_membership_plans(): void
    {
        $role = new Feature;
        $relation = $role->membershipPlans();

        $this->assertEquals('feature_membership_plan', $relation->getTable());
        $this->assertEquals('feature_membership_plan.feature_id', $relation->getQualifiedForeignPivotKeyName());
        $this->assertEquals('feature_membership_plan.membership_plan_id', $relation->getQualifiedRelatedPivotKeyName());
        $this->assertEquals('features.id', $relation->getQualifiedParentKeyName());
    }
}
