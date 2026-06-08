<?php

declare(strict_types=1);

/**
 * Eloquent-backed fixture models for repository tests.
 *
 * This file complements tests/Fixtures/Models/*.php (which are mostly
 * empty plain-class stubs used purely for satisfying mock/type hints).
 * The repository tests need actual BaseModelAbstract subclasses so they
 * can pass type checks on contracts like
 * `MembershipPlanRateRepositoryContract::create(..., ?BaseModelAbstract
 * $relatedModel)`.
 *
 * Rather than upgrade the existing fixture stubs (which would risk
 * conflicting with PR #11 / require coordinated changes across the
 * tests suite), we declare additional Eloquent-backed fixtures here under
 * the Polis\Tests\Fixtures\Repository namespace and either:
 *
 *  (a) instantiate them directly in tests (no class_alias needed), or
 *  (b) — for consumer-app FQCNs that haven't already been aliased by a
 *       pre-existing fixture — register a class_alias here.
 *
 * Load order: tests/bootstrap.php loads tests/Fixtures/Vendor/* first
 * (which installs our EloquentJoinBuilder + EloquentJoin stubs), then
 * tests/Fixtures/Stripe/*, then tests/Fixtures/Models/*. This file is
 * NOT auto-globbed by tests/bootstrap.php because it lives outside
 * tests/Fixtures/Models — we deliberately load it from the
 * RepositoryTestCase / individual tests when needed, so it doesn't fight
 * with the existing class_alias declarations in tests/Fixtures/Models/*.
 *
 * For an Eloquent stand-in for a consumer-app model that doesn't already
 * have an alias from tests/Fixtures/Models, declare a class here and
 * alias it inside the conditional guard.
 */

namespace Polis\Tests\Fixtures\Repository;

use Polis\Models\BaseModelAbstract;

/**
 * Eloquent stub for any consumer-app model whose only need is to satisfy
 * a `?Polis\Models\BaseModelAbstract` type hint in a method signature.
 *
 * Tests can construct this directly (or Mockery::mock(EloquentBaseStub::class))
 * and pass it as a $relatedModel argument without triggering the TypeError
 * we hit when a plain-class fixture (e.g. tests/Fixtures/Models/MembershipPlan.php)
 * gets handed to a method that requires a BaseModelAbstract.
 */
class EloquentBaseStub extends BaseModelAbstract
{
    protected $table = 'eloquent_base_stubs';

    protected $guarded = [];
}
