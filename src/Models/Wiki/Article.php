<?php

declare(strict_types=1);

namespace Polis\Models\Wiki;

use App\Models\Category;
use App\Models\Organization\Organization;
use App\Models\Resource;
use App\Models\Statistic\TargetStatistic;
use App\Models\User\ArticleNote;
use App\Models\User\User;
use App\Models\Wiki\ArticleIteration;
use App\Models\Wiki\ArticleModification;
use App\Models\Wiki\ArticleSummary;
use App\Models\Wiki\ArticleVersion;
use Eloquent;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Polis\Contracts\Models\BelongsToOrganizationContract;
use Polis\Contracts\Models\CanBeIndexedContract;
use Polis\Contracts\Models\CanBeStatisticTargetContract;
use Polis\Contracts\Models\HasPolicyContract;
use Polis\Contracts\Models\HasValidationRulesContract;
use Polis\Models\BaseModelAbstract;
use Polis\Models\Traits\BelongsToOrganization;
use Polis\Models\Traits\CanBeIndexed;
use Polis\Models\Traits\HasStatisticTargets;
use Polis\Models\Traits\HasValidationRules;
use Polis\Models\Traits\IsOwnedByEntity;

/**
 * Class Article
 *
 * @property int $id
 * @property int $created_by_id
 * @property int|null $organization_id
 * @property int|null $owner_id
 * @property string|null $owner_type
 * @property string $title
 * @property-read Organization|null $organization
 * @property-read Model|Eloquent|null $owner
 * @property Carbon|null $deleted_at
 * @property mixed|null $created_at
 * @property mixed|null $updated_at
 * @property-read User $createdBy
 * @property-read null|string $content
 * @property-read null|ArticleVersion $current_version
 * @property-read null|string $last_iteration_content
 * @property-read Collection|ArticleIteration[] $iterations
 * @property-read int|null $iterations_count
 * @property-read Collection|ArticleVersion[] $versions
 * @property-read int|null $versions_count
 *
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder|\App\Models\Wiki\Article newModelQuery()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder|\App\Models\Wiki\Article newQuery()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder|\App\Models\Wiki\Article query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Wiki\Article whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Wiki\Article whereCreatedById($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Wiki\Article whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Wiki\Article whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Wiki\Article whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Wiki\Article whereUpdatedAt($value)
 *
 * @mixin Eloquent
 *
 * @property string|null $url
 * @property string|null $authors
 * @property int $has_full_modification_history
 * @property-read Collection<int, ArticleNote> $articleNotes
 * @property-read int|null $article_notes_count
 * @property-read Collection<int, Category> $categories
 * @property-read int|null $categories_count
 * @property-read Collection<int, ArticleModification> $modifications
 * @property-read int|null $modifications_count
 * @property-read \App\Models\Resource|null $resource
 * @property-read Collection<int, TargetStatistic> $targetStatistics
 * @property-read int|null $target_statistics_count
 *
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Article getAggregateMethod()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Article isAppendRelationsCount()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Article isLeftJoin()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Article isUseTableAlias()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Article joinRelations($relations, $leftJoin = null)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Article onlyTrashed()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Article orWhereInJoin($column, $values)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Article orWhereJoin($column, $operator, $value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Article orWhereNotInJoin($column, $values)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Article orderByJoin($column, $direction = 'asc', $aggregateMethod = null)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Article setAggregateMethod(string $aggregateMethod)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Article setAppendRelationsCount(bool $appendRelationsCount)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Article setLeftJoin(bool $leftJoin)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Article setUseTableAlias(bool $useTableAlias)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Article whereAuthors($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Article whereHasFullModificationHistory($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Article whereInJoin($column, $values, $boolean = 'and', $not = false)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Article whereJoin($column, $operator, $value, $boolean = 'and')
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Article whereNotInJoin($column, $values, $boolean = 'and')
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|Article whereUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Article withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Article withoutTrashed()
 *
 * @mixin Eloquent
 */
class Article extends BaseModelAbstract implements BelongsToOrganizationContract, CanBeIndexedContract, CanBeStatisticTargetContract, HasPolicyContract, HasValidationRulesContract
{
    use BelongsToOrganization, CanBeIndexed, HasStatisticTargets, HasValidationRules, IsOwnedByEntity;

    /**
     * Values that are appending on a toArray function call
     *
     * @var array
     */
    protected $appends = [
        'content',
        'last_iteration_content',
    ];

    /**
     * The user that originally created this article
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    /**
     * All of the iterations
     */
    public function iterations(): HasMany
    {
        return $this->hasMany(ArticleIteration::class)
            ->orderByDesc('created_at')->orderByDesc('id');
    }

    /**
     * All modifications for this article
     */
    public function modifications(): HasMany
    {
        return $this->hasMany(ArticleModification::class)
            ->orderByDesc('created_at')->orderByDesc('id');
    }

    /**
     * All versions related to this article
     */
    public function versions(): HasMany
    {
        return $this->hasMany(ArticleVersion::class)
            ->orderByDesc('created_at')->orderByDesc('id');
    }

    /**
     * All categories associated with this article
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'article_category')
            ->withPivot('relevance')
            ->withTimestamps();
    }

    /**
     * All notes associated with this article
     */
    public function articleNotes(): HasMany
    {
        return $this->hasMany(ArticleNote::class);
    }

    /**
     * The summary for this article
     */
    public function articleSummary(): HasOne
    {
        return $this->hasOne(ArticleSummary::class);
    }

    /**
     * Gets the content of the article
     */
    public function getContentAttribute(): ?string
    {
        return $this->current_version?->articleIteration?->content;
    }

    /**
     * Gets the content of the article
     */
    public function getCurrentVersionAttribute(): ?ArticleVersion
    {
        return $this->versions()->limit(1)->get()->first();
    }

    /**
     * Gets the content of the article
     */
    public function getLastIterationContentAttribute(): ?string
    {
        if (isset($this->attributes['last_iteration_content'])) {
            return $this->attributes['last_iteration_content'];
        }
        /** @var ArticleIteration|null $iteration */
        $iteration = $this->iterations()->limit(1)->get()->first();

        return $iteration ? $iteration->content : null;
    }

    public function morphRelationName(): string
    {
        return 'article';
    }

    /**
     * Gets the content that will be indexed for this resource
     */
    public function getContentString(): ?string
    {
        return $this->title.' '.($this->content ?? '');
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
                'title' => [
                    'string',
                    'max:120',
                ],
                'url' => [
                    'nullable',
                    'string',
                    'url',
                ],
                'organization_id' => [
                    'nullable',
                    'integer',
                ],
                'owner_id' => [
                    'nullable',
                    'integer',
                ],
                'owner_type' => [
                    'nullable',
                    'string',
                ],
                'authors' => [
                    'nullable',
                    'string',
                ],
                'categories' => [
                    'array',
                ],
                'categories.*.category_id' => [
                    'integer',
                    'exists:categories,id',
                ],
                'categories.*.relevance' => [
                    'numeric',
                ],
            ],
            static::VALIDATION_RULES_CREATE => [
                static::VALIDATION_PREPEND_REQUIRED => [
                    'title',
                ],
            ],
        ];
    }
}
