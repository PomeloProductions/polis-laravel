<?php
declare(strict_types=1);

namespace App\Models\User;

use Polis\Models\User\User as AtheniaUser;

/**
 * Class User
 *
 * @package App\Models\User
 * @property int $id
 * @property int|null $merged_to_id
 * @property string|null $stripe_customer_key
 * @property string $email
 * @property string|null $first_name
 * @property string|null $password
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property int $allow_users_to_add_me
 * @property int $receive_push_notifications
 * @property string|null $about_me
 * @property string|null $push_notification_key
 * @property int|null $profile_image_id
 * @property string|null $last_name
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User\ArticleNote> $articleNotes
 * @property-read int|null $article_notes_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Asset> $assets
 * @property-read int|null $assets_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Vote\BallotCompletion> $ballotCompletions
 * @property-read int|null $ballot_completions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Collection\Collection> $collections
 * @property-read int|null $collections_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Wiki\Article> $createdArticles
 * @property-read int|null $created_articles_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Wiki\ArticleIteration> $createdIterations
 * @property-read int|null $created_iterations_count
 * @property-read null|string $profile_image_url
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Messaging\Message> $messages
 * @property-read int|null $messages_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Organization\OrganizationManager> $organizationManagers
 * @property-read int|null $organization_managers_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Payment\PaymentMethod> $paymentMethods
 * @property-read int|null $payment_methods_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Payment\Payment> $payments
 * @property-read int|null $payments_count
 * @property-read \Polis\Models\User\ProfileImage|null $profileImage
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Messaging\PushNotificationKey> $pushNotificationKeys
 * @property-read int|null $push_notification_keys_count
 * @property-read \App\Models\Resource|null $resource
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Role> $roles
 * @property-read int|null $roles_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Subscription\Subscription> $subscriptions
 * @property-read int|null $subscriptions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Messaging\Thread> $threads
 * @property-read int|null $threads_count
 * @method static \Database\Factories\User\UserFactory factory($count = null, $state = [])
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|User getAggregateMethod()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|User isAppendRelationsCount()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|User isLeftJoin()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|User isUseTableAlias()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|User joinRelations($relations, $leftJoin = null)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|User newModelQuery()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User onlyTrashed()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|User orWhereInJoin($column, $values)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|User orWhereJoin($column, $operator, $value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|User orWhereNotInJoin($column, $values)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|User orderByJoin($column, $direction = 'asc', $aggregateMethod = null)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|User query()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|User setAggregateMethod(string $aggregateMethod)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|User setAppendRelationsCount(bool $appendRelationsCount)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|User setLeftJoin(bool $leftJoin)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|User setUseTableAlias(bool $useTableAlias)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|User whereAboutMe($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|User whereAllowUsersToAddMe($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|User whereCreatedAt($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|User whereDeletedAt($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|User whereEmail($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|User whereFirstName($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|User whereId($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|User whereInJoin($column, $values, $boolean = 'and', $not = false)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|User whereJoin($column, $operator, $value, $boolean = 'and')
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|User whereLastName($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|User whereMergedToId($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|User whereNotInJoin($column, $values, $boolean = 'and')
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|User wherePassword($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|User whereProfileImageId($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|User wherePushNotificationKey($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|User whereReceivePushNotifications($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|User whereStripeCustomerKey($value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|User whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withoutTrashed()
 * @mixin \Eloquent
 */
class User extends AtheniaUser
{
    public function buildModelValidationRules(...$params): array
    {
        $rules = parent::buildModelValidationRules(...$params);

        $rules[static::VALIDATION_RULES_BASE]['time_format'] = [
            'string',
            'in:12h,24h',
        ];

        return $rules;
    }
}