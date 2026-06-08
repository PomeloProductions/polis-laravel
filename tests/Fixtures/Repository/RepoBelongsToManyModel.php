<?php

declare(strict_types=1);

namespace Polis\Tests\Fixtures\Repository;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Polis\Models\BaseModelAbstract;

/**
 * BelongsToMany Eloquent fixture used to exercise the BelongsToMany branch
 * of BaseRepositoryAbstract::create() — save-then-attach via pivot table.
 *
 * The inverse-side relationship (back to RepoParentModel) is also defined
 * so the BaseRepositoryAbstract create() path can call attach() on the
 * relation built from this side.
 */
class RepoBelongsToManyModel extends BaseModelAbstract
{
    protected $table = 'repo_belongs_to_many_models';

    protected $guarded = [];

    public function repoParentModels(): BelongsToMany
    {
        return $this->belongsToMany(
            RepoParentModel::class,
            'repo_parent_repo_belongs_to_many',
            'repo_belongs_to_many_model_id',
            'repo_parent_model_id',
        );
    }
}
