<?php

declare(strict_types=1);

namespace Polis\Tests\Fixtures\Repository;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Polis\Models\BaseModelAbstract;

/**
 * Test-only Eloquent model used as the "parent" in BaseRepositoryAbstract
 * relationship-inference tests.
 *
 * Defines all four relationship types BaseRepositoryAbstract::create()
 * branches on (HasMany, HasOne, BelongsToMany — BelongsTo is exercised via
 * RepoChildModel pointing back at this) so a single fixture model exercises
 * every case. Also exposes a singular alias method (`repoChildModel`) to
 * exercise the singular fallback in getRelationshipFunctionName().
 */
class RepoParentModel extends BaseModelAbstract
{
    protected $table = 'repo_parent_models';

    protected $guarded = [];

    public function repoChildModels(): HasMany
    {
        return $this->hasMany(RepoChildModel::class);
    }

    public function repoHasOneModel(): HasOne
    {
        return $this->hasOne(RepoHasOneModel::class);
    }

    public function repoBelongsToManyModels(): BelongsToMany
    {
        return $this->belongsToMany(
            RepoBelongsToManyModel::class,
            'repo_parent_repo_belongs_to_many',
            'repo_parent_model_id',
            'repo_belongs_to_many_model_id',
        );
    }
}
