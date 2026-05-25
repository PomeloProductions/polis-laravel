<?php

declare(strict_types=1);

namespace Polis\Models\Wiki;

use App\Models\Wiki\Article;
use App\Models\Wiki\ArticleIteration;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Polis\Contracts\Models\HasValidationRulesContract;
use Polis\Models\BaseModelAbstract;
use Polis\Models\Traits\HasValidationRules;
use Polis\Validators\ArticleVersion\SelectedIterationBelongsToArticleValidator;

/**
 * Class ArticleVersion
 *
 * @property int $id
 * @property int $article_id
 * @property int $iteration_id
 * @property string|null $name
 * @property Carbon|null $deleted_at
 * @property mixed|null $created_at
 * @property mixed|null $updated_at
 * @property-read Article $article
 * @property-read ArticleIteration $articleIteration
 *
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder|\App\Models\Wiki\ArticleVersion newModelQuery()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder|\App\Models\Wiki\ArticleVersion newQuery()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder|\App\Models\Wiki\ArticleVersion query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Wiki\ArticleVersion whereArticleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Wiki\ArticleVersion whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Wiki\ArticleVersion whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Wiki\ArticleVersion whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Wiki\ArticleVersion whereIterationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Wiki\ArticleVersion whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Wiki\ArticleVersion whereUpdatedAt($value)
 *
 * @property int $article_iteration_id
 *
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ArticleVersion getAggregateMethod()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ArticleVersion isAppendRelationsCount()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ArticleVersion isLeftJoin()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ArticleVersion isUseTableAlias()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ArticleVersion joinRelations($relations, $leftJoin = null)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ArticleVersion onlyTrashed()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ArticleVersion orWhereInJoin($column, $values)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ArticleVersion orWhereJoin($column, $operator, $value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ArticleVersion orWhereNotInJoin($column, $values)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ArticleVersion orderByJoin($column, $direction = 'asc', $aggregateMethod = null)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ArticleVersion setAggregateMethod(string $aggregateMethod)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ArticleVersion setAppendRelationsCount(bool $appendRelationsCount)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ArticleVersion setLeftJoin(bool $leftJoin)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ArticleVersion setUseTableAlias(bool $useTableAlias)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ArticleVersion whereArticleIterationId($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ArticleVersion whereInJoin($column, $values, $boolean = 'and', $not = false)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ArticleVersion whereJoin($column, $operator, $value, $boolean = 'and')
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ArticleVersion whereNotInJoin($column, $values, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ArticleVersion withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ArticleVersion withoutTrashed()
 *
 * @mixin \Eloquent
 */
class ArticleVersion extends BaseModelAbstract implements HasValidationRulesContract
{
    use HasValidationRules;

    /**
     * The article this version is for
     */
    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    /**
     * The iteration that this version is for
     */
    public function articleIteration(): BelongsTo
    {
        return $this->belongsTo(ArticleIteration::class);
    }

    /**
     * Build the model validation rules
     *
     * @param  array  $params
     */
    public function buildModelValidationRules(...$params): array
    {
        return [
            static::VALIDATION_RULES_BASE => [
                'article_iteration_id' => [
                    'bail',
                    'required',
                    'int',
                    Rule::exists('article_iterations', 'id'),
                    SelectedIterationBelongsToArticleValidator::KEY,
                ],
            ],
        ];
    }
}
