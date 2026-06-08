<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Repositories;

use Polis\Repositories\BaseRepositoryAbstract;
use Polis\Tests\Fixtures\Repository\RepoBelongsToManyModel;
use Polis\Tests\Fixtures\Repository\RepoChildModel;
use Polis\Tests\Fixtures\Repository\RepoHasOneModel;
use Polis\Tests\Fixtures\Repository\RepoParentModel;
use Polis\Tests\Fixtures\Repository\RepositoryTestCase;

/**
 * Comprehensive Eloquent-backed tests for BaseRepositoryAbstract.
 *
 * The original tests/Unit/Repositories/BaseRepositoryAbstractTest.php (kept
 * in the Consumer-Only bucket) is reflection-only and was written before
 * the `update()`/`delete()` signatures required a BaseModelAbstract — it
 * no longer runs cleanly with the current source. These tests replace it
 * with end-to-end exercise of every public method against real Eloquent
 * fixture models and an in-memory sqlite database.
 *
 * Branches covered here:
 *  - create() with no parent
 *  - create() with BelongsTo parent (foreign key set on new model)
 *  - create() with BelongsToMany parent (save then attach via pivot)
 *  - create() with HasMany parent (save then set FK on related model)
 *  - create() with HasOne parent (same branch as HasMany)
 *  - create() with forcedValues (assign post-newInstance)
 *  - getRelationshipFunctionName() plural-success path
 *  - getRelationshipFunctionName() singular-fallback path
 *  - update() happy path
 *  - update() with forcedValues -> forceFill called
 *  - update() failure -> DomainException
 *  - delete() happy path
 *  - delete() failure -> DomainException
 *  - findOrFail() happy path + with-relations
 *  - findOrFail() missing -> ModelNotFoundException
 *  - findAll() with no filters (paginated)
 *  - findAll() with limit=0 (returns collection)
 *  - findAll() filter variants: equality, in, not in, IS NULL, IS NOT NULL, operator
 *  - findAll() searches (orWhere variants)
 *  - findAll() with orderBy
 *  - findAll() with belongsToArray (BelongsTo + BelongsToMany)
 */
final class BaseRepositoryAbstractFixtureTest extends RepositoryTestCase
{
    /**
     * Build a concrete anonymous repository wrapping a model instance.
     */
    private function buildRepository($model): BaseRepositoryAbstract
    {
        return new class($model, $this->getGenericLogMock()) extends BaseRepositoryAbstract {};
    }

    public function test_create_persists_model_when_no_parent(): void
    {
        $repo = $this->buildRepository(new RepoParentModel);

        /** @var RepoParentModel $created */
        $created = $repo->create(['name' => 'first']);

        $this->assertNotNull($created->id);
        $this->assertSame('first', $created->name);
        $this->assertDatabaseCount('repo_parent_models', 1);
    }

    public function test_create_applies_forced_values_after_new_instance(): void
    {
        $repo = $this->buildRepository(new RepoParentModel);

        /** @var RepoParentModel $created */
        $created = $repo->create([], null, ['name' => 'forced']);

        $this->assertSame('forced', $created->name);
        $this->assertDatabaseHas('repo_parent_models', ['name' => 'forced']);
    }

    public function test_create_with_belongs_to_parent_sets_foreign_key(): void
    {
        $parent = RepoParentModel::query()->create(['name' => 'p']);

        $repo = $this->buildRepository(new RepoChildModel);
        /** @var RepoChildModel $child */
        $child = $repo->create(['label' => 'c'], $parent);

        $this->assertSame($parent->id, $child->repo_parent_model_id);
        $this->assertDatabaseHas('repo_child_models', [
            'label' => 'c',
            'repo_parent_model_id' => $parent->id,
        ]);
    }

    public function test_create_with_has_one_parent_saves_then_assigns_fk(): void
    {
        // Setup: a parent that defines hasOne(RepoHasOneModel)
        $parent = RepoParentModel::query()->create(['name' => 'p']);

        // Use the parent repository whose model is RepoParentModel, and
        // pass a RepoHasOneModel as the related parent so the relationship
        // function name `repoHasOneModel` resolves (singular fallback after
        // plural `repoHasOneModels` not existing).
        $repo = $this->buildRepository(new RepoParentModel);
        // Switch model side: use repo on child to call into parent's
        // HasOne. Actually BaseRepositoryAbstract::create($data,
        // $relatedModel) looks up the relationship function on $this->model
        // — so to exercise HasOne we make the **new** model RepoParentModel
        // and the **related** RepoHasOneModel. The function on parent is
        // `repoHasOneModel` — a HasOne. We exercise the HasMany/HasOne
        // branch.
        $hasOne = new RepoHasOneModel;
        $hasOne->payload = 'preexisting';

        /** @var RepoParentModel $newParent */
        $newParent = $repo->create(['name' => 'newp'], $hasOne);

        $this->assertNotNull($newParent->id);
        // The HasOne path saves the new model and then sets the FK on the
        // related model, so the related "preexisting" RepoHasOneModel
        // should end up with repo_parent_model_id = $newParent->id.
        $this->assertSame($newParent->id, $hasOne->repo_parent_model_id);
    }

    public function test_create_with_belongs_to_many_parent_attaches_via_pivot(): void
    {
        $other = RepoBelongsToManyModel::query()->create(['tag' => 'related']);

        $repo = $this->buildRepository(new RepoParentModel);
        /** @var RepoParentModel $parent */
        $parent = $repo->create(['name' => 'with-pivot'], $other);

        $this->assertSame(1, $parent->repoBelongsToManyModels()->count());
        $this->assertDatabaseHas('repo_parent_repo_belongs_to_many', [
            'repo_parent_model_id' => $parent->id,
            'repo_belongs_to_many_model_id' => $other->id,
        ]);
    }

    public function test_create_relationship_name_inference_falls_back_to_singular(): void
    {
        // RepoChildModel exposes `repoParentModel` (singular) only — the
        // plural `repoParentModels` does not exist on it. Exercise the
        // fallback by creating a child related to a parent.
        $parent = RepoParentModel::query()->create(['name' => 'p']);

        $repo = $this->buildRepository(new RepoChildModel);
        /** @var RepoChildModel $child */
        $child = $repo->create(['label' => 'singular-fallback'], $parent);

        $this->assertSame($parent->id, $child->repo_parent_model_id);
    }

    public function test_update_persists_changes(): void
    {
        $model = RepoParentModel::query()->create(['name' => 'before']);

        $repo = $this->buildRepository(new RepoParentModel);
        $updated = $repo->update($model, ['name' => 'after']);

        $this->assertSame('after', $updated->name);
        $this->assertDatabaseHas('repo_parent_models', ['id' => $model->id, 'name' => 'after']);
    }

    public function test_update_with_forced_values_force_fills_before_update(): void
    {
        $model = RepoParentModel::query()->create(['name' => 'before']);

        $repo = $this->buildRepository(new RepoParentModel);
        $updated = $repo->update($model, ['name' => 'after'], ['name' => 'forced']);

        // forceFill applies the forced value, then update() applies $data
        // which overrides it again, leaving the final state from $data.
        $this->assertSame('after', $updated->name);
    }

    public function test_update_throws_domain_exception_when_eloquent_update_returns_false(): void
    {
        $log = $this->getGenericLogMock();

        $modelMock = \Mockery::mock(\Polis\Models\BaseModelAbstract::class)->makePartial();
        $modelMock->shouldReceive('update')->once()->with(['name' => 'x'])->andReturn(false);
        $modelMock->shouldReceive('getAttribute')->andReturn(7);

        $repo = new class($modelMock, $log) extends BaseRepositoryAbstract {};

        $this->expectException(\DomainException::class);
        $repo->update($modelMock, ['name' => 'x']);
    }

    public function test_delete_removes_model_from_database(): void
    {
        $model = RepoParentModel::query()->create(['name' => 'doomed']);

        $repo = $this->buildRepository(new RepoParentModel);
        $this->assertTrue($repo->delete($model));

        // soft-deleted via the BaseModelAbstract SoftDeletes trait
        $this->assertSoftDeleted('repo_parent_models', ['id' => $model->id]);
    }

    public function test_delete_throws_domain_exception_when_eloquent_delete_returns_false(): void
    {
        $log = $this->getGenericLogMock();

        $modelMock = \Mockery::mock(\Polis\Models\BaseModelAbstract::class)->makePartial();
        $modelMock->shouldReceive('delete')->once()->andReturn(false);
        $modelMock->shouldReceive('getAttribute')->andReturn(7);

        $repo = new class($modelMock, $log) extends BaseRepositoryAbstract {};

        $this->expectException(\DomainException::class);
        $repo->delete($modelMock);
    }

    public function test_find_or_fail_returns_model(): void
    {
        $model = RepoParentModel::query()->create(['name' => 'findable']);

        $repo = $this->buildRepository(new RepoParentModel);
        $found = $repo->findOrFail($model->id);

        $this->assertSame($model->id, $found->id);
    }

    public function test_find_or_fail_throws_when_missing(): void
    {
        $repo = $this->buildRepository(new RepoParentModel);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $repo->findOrFail(99999);
    }

    public function test_find_or_fail_loads_with_relations(): void
    {
        $parent = RepoParentModel::query()->create(['name' => 'p']);
        RepoChildModel::query()->create([
            'repo_parent_model_id' => $parent->id,
            'label' => 'a child',
        ]);

        $repo = $this->buildRepository(new RepoParentModel);
        $found = $repo->findOrFail($parent->id, ['repoChildModels']);

        $this->assertTrue($found->relationLoaded('repoChildModels'));
        $this->assertCount(1, $found->repoChildModels);
    }

    public function test_find_all_paginated_by_default(): void
    {
        RepoParentModel::query()->create(['name' => 'a']);
        RepoParentModel::query()->create(['name' => 'b']);

        $repo = $this->buildRepository(new RepoParentModel);
        $result = $repo->findAll();

        $this->assertInstanceOf(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class, $result);
        $this->assertSame(2, $result->total());
    }

    public function test_find_all_returns_collection_when_limit_zero(): void
    {
        RepoParentModel::query()->create(['name' => 'a']);

        $repo = $this->buildRepository(new RepoParentModel);
        $result = $repo->findAll([], [], [], [], 0);

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $result);
        $this->assertCount(1, $result);
    }

    public function test_find_all_filters_with_equality_key_value(): void
    {
        RepoParentModel::query()->create(['name' => 'keep']);
        RepoParentModel::query()->create(['name' => 'drop']);

        $repo = $this->buildRepository(new RepoParentModel);
        $result = $repo->findAll(['name' => 'keep'], [], [], [], 0);

        $this->assertCount(1, $result);
        $this->assertSame('keep', $result->first()->name);
    }

    public function test_find_all_filters_with_operator_array(): void
    {
        RepoParentModel::query()->create(['name' => 'aaa']);
        RepoParentModel::query()->create(['name' => 'zzz']);

        $repo = $this->buildRepository(new RepoParentModel);
        // operator form: [column, op, value]
        $result = $repo->findAll([['name', '<', 'm']], [], [], [], 0);

        $this->assertCount(1, $result);
        $this->assertSame('aaa', $result->first()->name);
    }

    public function test_find_all_filters_with_in(): void
    {
        RepoParentModel::query()->create(['name' => 'a']);
        RepoParentModel::query()->create(['name' => 'b']);
        RepoParentModel::query()->create(['name' => 'c']);

        $repo = $this->buildRepository(new RepoParentModel);
        $result = $repo->findAll([['name', 'in', ['a', 'c']]], [], [], [], 0);

        $this->assertCount(2, $result);
    }

    public function test_find_all_filters_with_not_in(): void
    {
        RepoParentModel::query()->create(['name' => 'a']);
        RepoParentModel::query()->create(['name' => 'b']);
        RepoParentModel::query()->create(['name' => 'c']);

        $repo = $this->buildRepository(new RepoParentModel);
        $result = $repo->findAll([['name', 'not in', ['a']]], [], [], [], 0);

        $this->assertCount(2, $result);
    }

    public function test_find_all_filters_with_is_null(): void
    {
        RepoParentModel::query()->create(['name' => null]);
        RepoParentModel::query()->create(['name' => 'b']);

        $repo = $this->buildRepository(new RepoParentModel);
        $result = $repo->findAll([['name', 'IS NULL']], [], [], [], 0);

        $this->assertCount(1, $result);
    }

    public function test_find_all_filters_with_is_not_null(): void
    {
        RepoParentModel::query()->create(['name' => null]);
        RepoParentModel::query()->create(['name' => 'b']);

        $repo = $this->buildRepository(new RepoParentModel);
        $result = $repo->findAll([['name', 'IS NOT NULL']], [], [], [], 0);

        $this->assertCount(1, $result);
        $this->assertSame('b', $result->first()->name);
    }

    public function test_find_all_with_searches_or_where(): void
    {
        RepoParentModel::query()->create(['name' => 'alpha']);
        RepoParentModel::query()->create(['name' => 'beta']);
        RepoParentModel::query()->create(['name' => 'gamma']);

        $repo = $this->buildRepository(new RepoParentModel);
        $result = $repo->findAll([], ['name' => 'alpha'], [], [], 0);

        $this->assertCount(1, $result);
        $this->assertSame('alpha', $result->first()->name);
    }

    public function test_find_all_searches_with_operator_array(): void
    {
        RepoParentModel::query()->create(['name' => 'aa']);
        RepoParentModel::query()->create(['name' => 'bb']);
        RepoParentModel::query()->create(['name' => 'cc']);

        $repo = $this->buildRepository(new RepoParentModel);
        $result = $repo->findAll([], [['name', '<', 'b']], [], [], 0);

        $this->assertCount(1, $result);
        $this->assertSame('aa', $result->first()->name);
    }

    public function test_find_all_searches_in_and_not_in(): void
    {
        RepoParentModel::query()->create(['name' => 'a']);
        RepoParentModel::query()->create(['name' => 'b']);
        RepoParentModel::query()->create(['name' => 'c']);

        $repo = $this->buildRepository(new RepoParentModel);

        $result = $repo->findAll([], [['name', 'in', ['a']]], [], [], 0);
        $this->assertCount(1, $result);

        $result = $repo->findAll([], [['name', 'not in', ['a', 'b']]], [], [], 0);
        $this->assertCount(1, $result);
    }

    public function test_find_all_searches_is_null_and_is_not_null(): void
    {
        RepoParentModel::query()->create(['name' => null]);
        RepoParentModel::query()->create(['name' => 'b']);

        $repo = $this->buildRepository(new RepoParentModel);

        $result = $repo->findAll([], [['name', 'IS NULL']], [], [], 0);
        $this->assertCount(1, $result);

        $result = $repo->findAll([], [['name', 'IS NOT NULL']], [], [], 0);
        $this->assertCount(1, $result);
    }

    public function test_find_all_with_order_by(): void
    {
        RepoParentModel::query()->create(['name' => 'b']);
        RepoParentModel::query()->create(['name' => 'a']);
        RepoParentModel::query()->create(['name' => 'c']);

        $repo = $this->buildRepository(new RepoParentModel);
        $result = $repo->findAll([], [], ['name' => 'asc'], [], 0);

        $names = $result->pluck('name')->all();
        $this->assertSame(['a', 'b', 'c'], $names);
    }

    public function test_find_all_with_belongs_to_array_filters_via_belongs_to_relationship(): void
    {
        $parentA = RepoParentModel::query()->create(['name' => 'pa']);
        $parentB = RepoParentModel::query()->create(['name' => 'pb']);

        RepoChildModel::query()->create(['repo_parent_model_id' => $parentA->id, 'label' => 'ca1']);
        RepoChildModel::query()->create(['repo_parent_model_id' => $parentA->id, 'label' => 'ca2']);
        RepoChildModel::query()->create(['repo_parent_model_id' => $parentB->id, 'label' => 'cb']);

        $repo = $this->buildRepository(new RepoChildModel);
        $result = $repo->findAll([], [], [], [], 0, [$parentA]);

        $this->assertCount(2, $result);
    }

    public function test_find_all_with_belongs_to_array_filters_via_belongs_to_many_relationship(): void
    {
        $parent = RepoParentModel::query()->create(['name' => 'p']);
        $otherA = RepoBelongsToManyModel::query()->create(['tag' => 'a']);
        $otherB = RepoBelongsToManyModel::query()->create(['tag' => 'b']);
        $parent->repoBelongsToManyModels()->attach([$otherA->id]);

        $repo = $this->buildRepository(new RepoBelongsToManyModel);
        // We pass $parent in belongsToArray; the inference resolves to
        // repoParentModels (BelongsToMany) on RepoBelongsToManyModel.
        $result = $repo->findAll([], [], [], [], 0, [$parent]);

        $this->assertCount(1, $result);
        $this->assertSame($otherA->id, $result->first()->id);
        $this->assertNotEquals($otherB->id, $result->first()->id);
    }

    public function test_find_all_with_pagination_page_argument(): void
    {
        for ($i = 1; $i <= 15; $i++) {
            RepoParentModel::query()->create(['name' => 'row-'.$i]);
        }

        $repo = $this->buildRepository(new RepoParentModel);
        $page2 = $repo->findAll([], [], [], [], 10, [], 2);

        $this->assertInstanceOf(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class, $page2);
        $this->assertSame(15, $page2->total());
        $this->assertSame(2, $page2->currentPage());
        $this->assertCount(5, $page2->items());
    }
}
