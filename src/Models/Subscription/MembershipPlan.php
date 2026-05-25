<?php

declare(strict_types=1);

namespace Polis\Models\Subscription;

use App\Models\Feature;
use App\Models\Subscription\MembershipPlanRate;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Polis\Contracts\Models\HasPolicyContract;
use Polis\Contracts\Models\HasValidationRulesContract;
use Polis\Models\BaseModelAbstract;
use Polis\Models\Traits\HasValidationRules;

/**
 * Class Plan
 *
 * @property int $id
 * @property string $name
 * @property string $duration
 * @property Carbon|null $deleted_at
 * @property mixed|null $created_at
 * @property mixed|null $updated_at
 * @property string|null $description
 * @property string $entity_type
 * @property bool $default
 * @property int|null $trial_period
 * @property-read MembershipPlanRate|null $currentRate
 * @property-read Collection|Feature[] $features
 * @property-read int|null $features_count
 * @property-read null|float $current_cost
 * @property-read null|float $current_rate_id
 * @property-read Collection|MembershipPlanRate[] $membershipPlanRates
 * @property-read int|null $membership_plan_rates_count
 *
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder|\App\Models\Subscription\MembershipPlan newModelQuery()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder|\App\Models\Subscription\MembershipPlan newQuery()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder|\App\Models\Subscription\MembershipPlan query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Subscription\MembershipPlan whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Subscription\MembershipPlan whereDefault($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Subscription\MembershipPlan whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Subscription\MembershipPlan whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Subscription\MembershipPlan whereDuration($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Subscription\MembershipPlan whereEntityType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Subscription\MembershipPlan whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Subscription\MembershipPlan whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Subscription\MembershipPlan whereTrialPeriod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Subscription\MembershipPlan whereUpdatedAt($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|MembershipPlan getAggregateMethod()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|MembershipPlan isAppendRelationsCount()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|MembershipPlan isLeftJoin()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|MembershipPlan isUseTableAlias()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|MembershipPlan joinRelations($relations, $leftJoin = null)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MembershipPlan onlyTrashed()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|MembershipPlan orWhereInJoin($column, $values)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|MembershipPlan orWhereJoin($column, $operator, $value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|MembershipPlan orWhereNotInJoin($column, $values)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|MembershipPlan orderByJoin($column, $direction = 'asc', $aggregateMethod = null)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|MembershipPlan setAggregateMethod(string $aggregateMethod)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|MembershipPlan setAppendRelationsCount(bool $appendRelationsCount)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|MembershipPlan setLeftJoin(bool $leftJoin)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|MembershipPlan setUseTableAlias(bool $useTableAlias)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|MembershipPlan whereInJoin($column, $values, $boolean = 'and', $not = false)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|MembershipPlan whereJoin($column, $operator, $value, $boolean = 'and')
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|MembershipPlan whereNotInJoin($column, $values, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MembershipPlan withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MembershipPlan withoutTrashed()
 *
 * @mixin \Eloquent
 */
class MembershipPlan extends BaseModelAbstract implements HasPolicyContract, HasValidationRulesContract
{
    use HasValidationRules;

    /**
     * @var string the enum value for the duration field when the plan only lasts a year
     */
    const DURATION_YEAR = 'year';

    /**
     * @var string the enum value for the duration field when the plan only lasts a month
     */
    const DURATION_MONTH = 'month';

    /**
     * @var string the enum value for the duration field when the plan lasts forever
     */
    const DURATION_LIFETIME = 'lifetime';

    /**
     * The available duration types for a membership plan
     */
    const AvailableDurations = [
        MembershipPlan::DURATION_MONTH,
        MembershipPlan::DURATION_YEAR,
        MembershipPlan::DURATION_LIFETIME,
    ];

    /**
     * Values that are appending on a toArray function call
     *
     * @var array
     */
    protected $appends = [
        'current_cost',
        'current_rate_id',
    ];

    /**
     * The current rate for this membership plan
     */
    public function currentRate(): HasOne
    {
        return $this->hasOne(MembershipPlanRate::class)
            ->where('active', true)
            ->orderBy('created_at', 'DESC');
    }

    public function features(): BelongsToMany
    {
        return $this->belongsToMany(Feature::class);
    }

    /**
     * All membership plan rates that have
     */
    public function membershipPlanRates(): HasMany
    {
        return $this->hasMany(MembershipPlanRate::class);
    }

    /**
     * Function that creates the current cost attribute
     *
     * @return null|float
     */
    public function getCurrentCostAttribute()
    {
        return $this->currentRate ? $this->currentRate->cost : null;
    }

    /**
     * Function that creates the current cost attribute
     *
     * @return null|float
     */
    public function getCurrentRateIdAttribute()
    {
        return $this->currentRate ? $this->currentRate->id : null;
    }

    /**
     * Build the model validation rules
     *
     * @param  array  $params  Any additional parameters needed
     */
    public function buildModelValidationRules(...$params): array
    {
        return [
            self::VALIDATION_RULES_BASE => [

                'name' => [
                    'string',
                    'max:120',
                ],

                'entity_type' => [
                    'string',
                    Rule::in([
                        'user',
                        'organization',
                    ]),
                ],

                'description' => [
                    'string',
                ],

                'current_cost' => [
                    'numeric',
                    'min:0.00',
                    'max:999999.99',
                ],

                'duration' => [
                    'string',
                    Rule::in(MembershipPlan::AvailableDurations),
                ],

                'trial_period' => [
                    'nullable',
                    'integer',
                    'min:0',
                ],

                'default' => [
                    'boolean',
                ],

                'features' => [
                    'array',
                ],

                'features.*' => [
                    'numeric',
                    Rule::exists('features', 'id'),
                ],
            ],
            self::VALIDATION_RULES_CREATE => [
                self::VALIDATION_PREPEND_REQUIRED => [
                    'name',
                    'entity_type',
                    'current_cost',
                    'duration',
                ],
            ],
            self::VALIDATION_RULES_UPDATE => [
                self::VALIDATION_PREPEND_NOT_PRESENT => [
                    'entity_type',
                    'duration',
                ],
            ],
        ];
    }

    /**
     * Swagger definition below
     *
     * @SWG\Definition (
     *     type="object",
     *     definition="MembershipPlan",
     *     description="The details of a membership plan",
     *
     *     @SWG\Property(
     *         property="id",
     *         type="integer",
     *         format="int32",
     *         readOnly=true
     *     ),
     *     @SWG\Property(
     *         property="created_at",
     *         type="string",
     *         format="date-time",
     *         description="UTC date of the time this was created",
     *         readOnly=true
     *     ),
     *     @SWG\Property(
     *         property="updated_at",
     *         type="string",
     *         format="date-time",
     *         description="UTC date of the time this was updated",
     *         readOnly=true
     *     ),
     *     @SWG\Property(
     *         property="current_cost",
     *         type="number",
     *         description="The current cost of the membership plan"
     *     ),
     *     @SWG\Property(
     *         property="current_rate_id",
     *         type="number",
     *         readonly=true,
     *         description="The current id of the membership plan rate"
     *     ),
     *     @SWG\Property(
     *         property="duration",
     *         type="string",
     *         maxLength=128,
     *         description="The duration for this membership plan"
     *     ),
     *     @SWG\Property(
     *         property="subscriptions",
     *         description="The subscriptions attatched to this membership plan",
     *         type="array",
     *
     *         @SWG\Items(ref="#/definitions/Subscription")
     *     ),
     * )
     */
}
