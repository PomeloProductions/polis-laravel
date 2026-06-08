<?php

declare(strict_types=1);

namespace Polis\Tests\Fixtures\Repository;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Polis\Models\BaseModelAbstract;

/**
 * Child Eloquent fixture used to exercise the BelongsTo branch of
 * BaseRepositoryAbstract::create() and the relationship-name inference
 * fallback (singular method name when plural doesn't exist on the model).
 *
 * Intentionally exposes both `repoParentModel` (singular) AND no plural
 * counterpart so the camel+plural lookup falls back to camel-singular.
 */
class RepoChildModel extends BaseModelAbstract
{
    protected $table = 'repo_child_models';

    protected $guarded = [];

    public function repoParentModel(): BelongsTo
    {
        return $this->belongsTo(RepoParentModel::class);
    }
}
