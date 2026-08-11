<?php
declare(strict_types=1);

namespace App\Models\Wiki;

use Polis\Models\Wiki\ArticleModification as AtheniaArticleModification;

/**
 * Class ArticleModification
 *
 * @package App\Models\Wiki
 * @property int $id
 * @property int $article_id
 * @property string $action
 * @property int $start_position
 * @property int|null $length
 * @property string|null $content
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Wiki\Article $article
 * @property-read \App\Models\Wiki\ArticleIteration|null $iteration
 * @method static \Database\Factories\Wiki\ArticleModificationFactory factory($count = null, $state = [])
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ArticleModification getAggregateMethod()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ArticleModification isAppendRelationsCount()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ArticleModification isLeftJoin()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ArticleModification isUseTableAlias()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ArticleModification joinRelations($relations, $leftJoin = null)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ArticleModification newModelQuery()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ArticleModification newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ArticleModification onlyTrashed()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ArticleModification orWhereInJoin($column, $values)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ArticleModification orWhereJoin($column, $operator, $value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ArticleModification orWhereNotInJoin($column, $values)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ArticleModification orderByJoin($column, $direction = 'asc', $aggregateMethod = null)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ArticleModification query()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ArticleModification setAggregateMethod(string $aggregateMethod)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ArticleModification setAppendRelationsCount(bool $appendRelationsCount)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ArticleModification setLeftJoin(bool $leftJoin)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ArticleModification setUseTableAlias(bool $useTableAlias)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ArticleModification whereAction($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ArticleModification whereArticleId($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ArticleModification whereContent($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ArticleModification whereCreatedAt($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ArticleModification whereDeletedAt($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ArticleModification whereId($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ArticleModification whereInJoin($column, $values, $boolean = 'and', $not = false)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ArticleModification whereJoin($column, $operator, $value, $boolean = 'and')
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ArticleModification whereLength($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ArticleModification whereNotInJoin($column, $values, $boolean = 'and')
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ArticleModification whereStartPosition($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|ArticleModification whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ArticleModification withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ArticleModification withoutTrashed()
 * @mixin \Eloquent
 */
class ArticleModification extends AtheniaArticleModification
{
}