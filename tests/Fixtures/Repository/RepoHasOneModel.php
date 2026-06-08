<?php

declare(strict_types=1);

namespace Polis\Tests\Fixtures\Repository;

use Polis\Models\BaseModelAbstract;

/**
 * Child Eloquent fixture used to exercise the HasOne/HasMany branch of
 * BaseRepositoryAbstract::create() (the branch that saves the new model
 * first, then sets the foreign key on the *related* model).
 */
class RepoHasOneModel extends BaseModelAbstract
{
    protected $table = 'repo_has_one_models';

    protected $guarded = [];
}
