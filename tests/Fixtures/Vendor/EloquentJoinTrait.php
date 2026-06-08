<?php

declare(strict_types=1);

/**
 * Fixture stub for AdminUI\Laravel\EloquentJoin\Traits\EloquentJoin and the
 * companion EloquentJoinBuilder class.
 *
 * The polis-laravel package's BaseModelAbstract uses the EloquentJoin trait
 * from the consumer-provided admin-ui/laravel-eloquent-join package, which
 * is NOT listed in this package's composer.json. Without a stub the
 * BaseModelAbstract class cannot be loaded at all in standalone tests —
 * which in turn blocks any test that needs an `instanceof BaseModelAbstract`
 * check (e.g. SubscriptionRepositoryContract::update($model)).
 *
 * BaseRepositoryAbstract also type-hints EloquentJoinBuilder in its
 * findAll() machinery (the search-closure parameter, the finalize-helper
 * argument). Tests that exercise findAll() against a real Eloquent model
 * therefore need (a) the trait to be usable on BaseModelAbstract, AND
 * (b) the Eloquent query builder to satisfy `instanceof EloquentJoinBuilder`
 * and expose whereJoin/orWhereJoin shims.
 *
 * Strategy:
 *   1. Define EloquentJoinBuilder as a thin subclass of Illuminate's
 *      Eloquent\Builder that re-exposes the where/orWhere methods under
 *      the join-aware names BaseRepositoryAbstract uses (whereJoin,
 *      orWhereJoin, whereInJoin, etc). For test purposes the joins
 *      collapse to plain wheres — sufficient to exercise every branch in
 *      buildFindAllQuery.
 *   2. Define the EloquentJoin trait so it overrides newEloquentBuilder()
 *      to return our EloquentJoinBuilder instance, ensuring BaseModelAbstract
 *      derivatives produce builders that pass the type hint.
 *   3. Register both as aliases under the expected FQCNs.
 */

namespace Polis\Tests\Fixtures\Vendor;

use AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder;
use AdminUI\Laravel\EloquentJoin\Traits\EloquentJoin;
use Illuminate\Database\Eloquent\Builder;

if (! class_exists(EloquentJoinBuilder::class, false)) {

    /**
     * Test-only subclass of Eloquent\Builder that re-exposes the four
     * "join" wrappers BaseRepositoryAbstract uses. For tests we don't
     * need real joins — the column lookups still work against a single
     * table, and that's all our fixture models exercise.
     */
    class EloquentJoinBuilderStub extends Builder
    {
        /**
         * Plain whereJoin -> where. EloquentJoin's real implementation
         * applies cross-table joins; we collapse to a simple WHERE.
         */
        public function whereJoin($column, $operator = null, $value = null, $boolean = 'and')
        {
            return $this->where($column, $operator, $value, $boolean);
        }

        public function orWhereJoin($column, $operator = null, $value = null)
        {
            return $this->orWhere($column, $operator, $value);
        }

        public function whereInJoin($column, $values, $boolean = 'and', $not = false)
        {
            return $this->whereIn($column, $values, $boolean, $not);
        }

        public function orWhereInJoin($column, $values)
        {
            return $this->orWhereIn($column, $values);
        }

        public function whereNotInJoin($column, $values, $boolean = 'and')
        {
            return $this->whereNotIn($column, $values, $boolean);
        }

        public function orderByJoin($column, $direction = 'asc', $aggregateMethod = null)
        {
            return $this->orderBy($column, $direction);
        }
    }

    class_alias(
        EloquentJoinBuilderStub::class,
        EloquentJoinBuilder::class,
    );
}

if (! trait_exists(EloquentJoin::class, false)) {

    trait EloquentJoinTrait
    {
        /**
         * Force models that use this trait to construct our test-only
         * EloquentJoinBuilder subclass — that's what makes the type hint
         * on BaseRepositoryAbstract::buildFindAllQuery's closures resolve
         * against a real query builder when tests use the fixture models.
         */
        public function newEloquentBuilder($query)
        {
            return new EloquentJoinBuilder($query);
        }
    }

    class_alias(
        EloquentJoinTrait::class,
        EloquentJoin::class,
    );
}
