<?php

declare(strict_types=1);

namespace Polis\Models\User;

use App\Models\Asset;
use App\Models\Messaging\Message;
use App\Models\Messaging\PushNotificationKey;
use App\Models\Messaging\Thread;
use App\Models\Organization\Organization;
use App\Models\Organization\OrganizationManager;
use App\Models\Payment\Payment;
use App\Models\Payment\PaymentMethod;
use App\Models\Resource;
use App\Models\Role;
use App\Models\Subscription\Subscription;
use App\Models\User\ArticleNote;
use App\Models\User\UserPage;
use App\Models\Vote\BallotCompletion;
use App\Models\Wiki\Article;
use App\Models\Wiki\ArticleIteration;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;
use Polis\Contracts\Models\CanBeIndexedContract;
use Polis\Contracts\Models\HasPolicyContract;
use Polis\Contracts\Models\HasValidationRulesContract;
use Polis\Contracts\Models\IsAnEntityContract;
use Polis\Contracts\Models\Messaging\CanReceiveEmailsContract;
use Polis\Contracts\Models\Messaging\CanReceiveMessageContract;
use Polis\Contracts\Models\Messaging\CanReceivePushNotificationContract;
use Polis\Contracts\Models\Messaging\CanReceiveSMSContract;
use Polis\Models\BaseModelAbstract;
use Polis\Models\Traits\CanBeIndexed;
use Polis\Models\Traits\HasValidationRules;
use Polis\Models\Traits\IsEntity;
use Polis\Policies\BasePolicyAbstract;

/**
 * Class User
 *
 * @property int $id
 * @property int|null $merged_to_id
 * @property string|null $stripe_customer_key
 * @property string $email
 * @property string|null $first_name
 * @property string $password
 * @property mixed|null $created_at
 * @property mixed|null $updated_at
 * @property Carbon|null $deleted_at
 * @property bool $allow_users_to_add_me
 * @property bool $receive_push_notifications
 * @property string|null $about_me
 * @property string|null $push_notification_key
 * @property int|null $profile_image_id
 * @property string|null $last_name
 * @property-read Collection|Asset[] $assets
 * @property-read int|null $assets_count
 * @property-read Collection|BallotCompletion[] $ballotCompletions
 * @property-read int|null $ballot_completions_count
 * @property-read Collection|Article[] $createdArticles
 * @property-read int|null $created_articles_count
 * @property-read Collection|ArticleIteration[] $createdIterations
 * @property-read int|null $created_iterations_count
 * @property-read null|string $profile_image_url
 * @property-read bool $is_super_admin
 * @property-read Collection|Message[] $messages
 * @property-read int|null $messages_count
 * @property-read Collection|OrganizationManager[] $organizationManagers
 * @property-read int|null $organization_managers_count
 * @property-read Collection|PaymentMethod[] $paymentMethods
 * @property-read int|null $payment_methods_count
 * @property-read Collection|Payment[] $payments
 * @property-read int|null $payments_count
 * @property-read \App\Models\User\ProfileImage|null $profileImage
 * @property-read \App\Models\Resource|null $resource
 * @property-read Collection|Role[] $roles
 * @property-read int|null $roles_count
 * @property-read Collection|Subscription[] $subscriptions
 * @property-read int|null $subscriptions_count
 * @property-read Collection|Thread[] $threads
 * @property-read int|null $threads_count
 *
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder|\App\Models\User\User newModelQuery()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder|\App\Models\User\User newQuery()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder|\App\Models\User\User query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User\User whereAboutMe($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User\User whereAllowUsersToAddMe($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User\User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User\User whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User\User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User\User whereFirstName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User\User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User\User whereLastName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User\User whereMergedToId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User\User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User\User whereProfileImageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User\User wherePushNotificationKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User\User whereReceivePushNotifications($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User\User whereStripeCustomerKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User\User whereUpdatedAt($value)
 *
 * @property-read Collection<int, ArticleNote> $articleNotes
 * @property-read int|null $article_notes_count
 * @property-read Collection<int, \App\Models\Collection\Collection> $collections
 * @property-read int|null $collections_count
 * @property-read Collection<int, PushNotificationKey> $pushNotificationKeys
 * @property-read int|null $push_notification_keys_count
 *
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|User getAggregateMethod()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|User isAppendRelationsCount()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|User isLeftJoin()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|User isUseTableAlias()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|User joinRelations($relations, $leftJoin = null)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User onlyTrashed()
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|User orWhereInJoin($column, $values)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|User orWhereJoin($column, $operator, $value)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|User orWhereNotInJoin($column, $values)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|User orderByJoin($column, $direction = 'asc', $aggregateMethod = null)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|User setAggregateMethod(string $aggregateMethod)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|User setAppendRelationsCount(bool $appendRelationsCount)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|User setLeftJoin(bool $leftJoin)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|User setUseTableAlias(bool $useTableAlias)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|User whereInJoin($column, $values, $boolean = 'and', $not = false)
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|User whereJoin($column, $operator, $value, $boolean = 'and')
 * @method static \AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder<static>|User whereNotInJoin($column, $values, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withoutTrashed()
 *
 * @mixin \Eloquent
 */
class User extends BaseModelAbstract implements AuthenticatableContract, CanBeIndexedContract, CanReceiveEmailsContract, CanReceiveMessageContract, CanReceivePushNotificationContract, CanReceiveSMSContract, HasPolicyContract, HasValidationRulesContract, IsAnEntityContract, JWTSubject
{
    use Authenticatable, CanBeIndexed, HasValidationRules, IsEntity;

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'deleted_at',
        'password',
    ];

    /**
     * The url of the profile image
     *
     * @var array
     */
    protected $appends = [
        'profile_image_url',
        'is_super_admin',
    ];

    /**
     * All assets this user has created
     */
    public function assets(): MorphMany
    {
        return $this->morphMany(Asset::class, 'owner');
    }

    /**
     * The ballot completions the user has done
     */
    public function ballotCompletions(): HasMany
    {
        return $this->hasMany(BallotCompletion::class);
    }

    /**
     * The article notes this user has created
     */
    public function articleNotes(): HasMany
    {
        return $this->hasMany(ArticleNote::class);
    }

    /**
     * The articles that were created by this user
     */
    public function createdArticles(): HasMany
    {
        return $this->hasMany(Article::class, 'created_by_id');
    }

    /**
     * The iterations that were created by this user
     */
    public function createdIterations(): HasMany
    {
        return $this->hasMany(ArticleIteration::class, 'created_by_id');
    }

    /**
     * The messages that were sent to a user
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'to_id');
    }

    /**
     * All organization manager relations this user has
     */
    public function organizationManagers(): HasMany
    {
        return $this->hasMany(OrganizationManager::class);
    }

    /**
     * The push notification keys that the push notification should be sent to
     */
    public function pushNotificationKeys(): HasMany
    {
        return $this->hasMany(PushNotificationKey::class);
    }

    /**
     * The asset that contains the profile image for this user
     */
    public function profileImage(): BelongsTo
    {
        return $this->belongsTo(ProfileImage::class);
    }

    /**
     * The resource object for this user
     */
    public function resource(): MorphOne
    {
        return $this->morphOne(Resource::class, 'resource');
    }

    /**
     * What roles this user has
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    /**
     * Any threads this user is apart of
     */
    public function threads(): BelongsToMany
    {
        return $this->belongsToMany(Thread::class);
    }

    /**
     * All pages belonging to this user
     */
    public function userPages(): HasMany
    {
        return $this->hasMany(UserPage::class)->orderBy('display_order');
    }

    /**
     * Add a Role to this user
     *
     * @return $this
     */
    public function addRole(int $roleId)
    {
        $this->roles()->attach($roleId);

        return $this;
    }

    /**
     * Does this have the role
     *
     * @param  mixed  $roles
     * @return bool
     */
    public function hasRole($roles)
    {
        $roles = (array) $roles;

        return $this->roles()->whereIn('id', $roles)->exists();
    }

    /**
     * Add a Role to this user
     *
     * @return $this
     */
    public function removeRole(int $roleId)
    {
        $this->roles()->detach($roleId);

        return $this;
    }

    /**
     * Determines whether or not the user can manage the organization.
     *
     * @param  int|array  $role
     *                           If the manager role is passed in then this will return true for both the manager role and admin role.
     *                           The admin role will only check for the admin role.
     */
    public function canManageOrganization(Organization $organization, $role = Role::MANAGER): bool
    {
        $roles = is_array($role) ? $role : [$role];
        if (! in_array(Role::ADMINISTRATOR, $roles)) {
            $roles[] = Role::ADMINISTRATOR;
        }

        return $this->organizationManagers->first(fn (OrganizationManager $organizationManager) => in_array($organizationManager->role_id, $roles) && $organizationManager->organization_id === $organization->id
        ) != null;
    }

    /**
     * Get the URL for the profile image
     */
    public function getProfileImageUrlAttribute(): ?string
    {
        return $this->profileImage?->url;
    }

    /**
     * Whether this user holds the platform super-admin role.
     *
     * Appended to the model's array/JSON form so the dashboard can tell,
     * from a single `GET /users/me` call, whether the user may manage any
     * organization (super admins bypass every org-scoped policy via
     * {@see BasePolicyAbstract::before()}). Consumers may
     * override this accessor if their app defines super-admin differently.
     */
    public function getIsSuperAdminAttribute(): bool
    {
        return $this->hasRole(Role::SUPER_ADMIN);
    }

    /**
     * {@inheritDoc}
     */
    public function canUserManageEntity(User $user, ?int $role = null): bool
    {
        return $this->id == $user->id;
    }

    /**
     * This will return if the message can be received by the specific model
     */
    public function canReceiveMessage(Message $message): bool
    {
        foreach ($message->via ?? [] as $via) {
            switch ($via) {
                case Message::VIA_EMAIL:
                    return true;
                case Message::VIA_PUSH_NOTIFICATION:
                    return (bool) $this->pushNotificationKeys->count();
                case Message::VIA_SMS:
                    return (bool) $this->getPhoneNumber();
            }
        }

        return false;
    }

    /**
     * The email address to send the email to
     */
    public function getEmailAddress(): string
    {
        return $this->email;
    }

    /**
     * The name of the person to be added as the to field
     */
    public function getEmailToName(): string
    {
        return $this->first_name.' '.$this->last_name;
    }

    /**
     * Gets the phone number for routing SMS messages
     */
    public function getPhoneNumber(): ?string
    {
        return $this->phone ?? null;
    }

    /**
     * The name of the morph relation
     */
    public function morphRelationName(): string
    {
        return 'user';
    }

    /**
     * Gets the content string to index
     */
    public function getContentString(): ?string
    {
        return $this->first_name.' '.$this->last_name;
    }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->id;
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    /**
     * Build the model validation rules
     *
     * @param  array  $params
     */
    public function buildModelValidationRules(...$params): array
    {
        $emailUnique = Rule::unique('users', 'email');

        $userId = count($params) ? $params[0]->id : null;

        if ($userId) {
            $emailUnique->ignore($userId);
        }

        return [
            static::VALIDATION_RULES_BASE => [
                'email' => [
                    'string',
                    'max:120',
                    'email',
                    $emailUnique,
                ],
                'first_name' => [
                    'string',
                    'max:120',
                ],
                'last_name' => [
                    'string',
                    'max:120',
                ],
                'password' => [
                    'string',
                    'min:6',
                ],
                'push_notification_key' => [
                    'string',
                    'max:512',
                ],
                'about_me' => [
                    'string',
                ],
                'allow_users_to_add_me' => [
                    'boolean',
                ],
                'receive_push_notifications' => [
                    'boolean',
                ],
            ],
        ];
    }
}
