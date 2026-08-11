<?php
declare(strict_types=1);

namespace App\Models\Wiki;

use Polis\Models\Wiki\ArticleIteration as AtheniaArticleIteration;

/**
 * Class ArticleIteration
 *
 * @package App\Models\Wiki
 * @property int $id
 * @property string $content
 * @property int $created_by_id
 * @property int $article_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property int|null $article_modification_id
 * @property-read \App\Models\Wiki\Article $article
 * @property-read \App\Models\User\User $createdBy
 * @property-read \App\Models\Wiki\ArticleModification|null $modification
 * @property-read \App\Models\Wiki\ArticleVersion|null $version
 * @method static \Database\Factories\Wiki\ArticleIterationFactory factory($count = null, $state = [])
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ArticleIteration getAggregateMethod()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ArticleIteration isAppendRelationsCount()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ArticleIteration isLeftJoin()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ArticleIteration isUseTableAlias()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ArticleIteration joinRelations($relations, $leftJoin = null)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ArticleIteration newModelQuery()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ArticleIteration newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ArticleIteration onlyTrashed()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ArticleIteration orWhereInJoin($column, $values)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ArticleIteration orWhereJoin($column, $operator, $value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ArticleIteration orWhereNotInJoin($column, $values)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ArticleIteration orderByJoin($column, $direction = 'asc', $aggregateMethod = null)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ArticleIteration query()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ArticleIteration setAggregateMethod(string $aggregateMethod)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ArticleIteration setAppendRelationsCount(bool $appendRelationsCount)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ArticleIteration setLeftJoin(bool $leftJoin)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ArticleIteration setUseTableAlias(bool $useTableAlias)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ArticleIteration whereArticleId($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ArticleIteration whereArticleModificationId($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ArticleIteration whereContent($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ArticleIteration whereCreatedAt($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ArticleIteration whereCreatedById($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ArticleIteration whereDeletedAt($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ArticleIteration whereId($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ArticleIteration whereInJoin($column, $values, $boolean = 'and', $not = false)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ArticleIteration whereJoin($column, $operator, $value, $boolean = 'and')
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ArticleIteration whereNotInJoin($column, $values, $boolean = 'and')
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ArticleIteration whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ArticleIteration withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ArticleIteration withoutTrashed()
 * @mixin \Eloquent
 */
class ArticleIteration extends AtheniaArticleIteration
{
}