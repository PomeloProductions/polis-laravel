<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Models\User;

use App\Models\Role;
use App\Models\Subscription\MembershipPlan;
use App\Models\Subscription\MembershipPlanRate;
use App\Models\Subscription\Subscription;
use App\Models\User\ProfileImage;
use App\Models\User\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Mockery;
use Polis\Tests\TestCase;

/**
 * Class UserTest
 */
final class UserTest extends TestCase
{
    public function test_assets(): void
    {
        $user = new User;
        $relation = $user->assets();

        $this->assertEquals('users.id', $relation->getQualifiedParentKeyName());
        $this->assertEquals('assets.owner_id', $relation->getQualifiedForeignKeyName());
    }

    public function test_ballot_completions(): void
    {
        $user = new User;
        $relation = $user->ballotCompletions();

        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertEquals('users.id', $relation->getQualifiedParentKeyName());
        $this->assertEquals('ballot_completions.user_id', $relation->getQualifiedForeignKeyName());
    }

    public function test_created_articles(): void
    {
        $user = new User;
        $relation = $user->createdArticles();

        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertEquals('users.id', $relation->getQualifiedParentKeyName());
        $this->assertEquals('articles.created_by_id', $relation->getQualifiedForeignKeyName());
    }

    public function test_created_iterations(): void
    {
        $user = new User;
        $relation = $user->createdIterations();

        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertEquals('users.id', $relation->getQualifiedParentKeyName());
        $this->assertEquals('article_iterations.created_by_id', $relation->getQualifiedForeignKeyName());
    }

    public function test_messages(): void
    {
        $user = new User;
        $relation = $user->messages();

        $this->assertEquals('users.id', $relation->getQualifiedParentKeyName());
        $this->assertEquals('messages.to_id', $relation->getQualifiedForeignKeyName());
    }

    public function test_organization_managers(): void
    {
        $user = new User;
        $relation = $user->organizationManagers();

        $this->assertEquals('users.id', $relation->getQualifiedParentKeyName());
        $this->assertEquals('organization_managers.user_id', $relation->getQualifiedForeignKeyName());
    }

    public function test_payments(): void
    {
        $user = new User;
        $relation = $user->payments();

        $this->assertEquals('users.id', $relation->getQualifiedParentKeyName());
        $this->assertEquals('payments.owner_id', $relation->getQualifiedForeignKeyName());
    }

    public function test_payment_methods(): void
    {
        $user = new User;
        $relation = $user->paymentMethods();

        $this->assertEquals('users.id', $relation->getQualifiedParentKeyName());
        $this->assertEquals('payment_methods.owner_id', $relation->getQualifiedForeignKeyName());
    }

    public function test_profile_image(): void
    {
        $model = new User;

        $relation = $model->profileImage();

        $this->assertEquals('users.profile_image_id', $relation->getQualifiedForeignKeyName());
        $this->assertEquals('assets.id', $relation->getQualifiedOwnerKeyName());
    }

    public function test_resource(): void
    {
        $user = new User;
        $relation = $user->resource();

        $this->assertEquals('resources.resource_id', $relation->getQualifiedForeignKeyName());
        $this->assertEquals('resources.resource_type', $relation->getQualifiedMorphType());
        $this->assertEquals('users.id', $relation->getQualifiedParentKeyName());
    }

    public function test_roles(): void
    {
        $role = new User;
        $relation = $role->roles();

        $this->assertEquals('role_user', $relation->getTable());
        $this->assertEquals('role_user.user_id', $relation->getQualifiedForeignPivotKeyName());
        $this->assertEquals('role_user.role_id', $relation->getQualifiedRelatedPivotKeyName());
        $this->assertEquals('users.id', $relation->getQualifiedParentKeyName());
    }

    public function test_subscriptions(): void
    {
        $user = new User;
        $relation = $user->subscriptions();

        $this->assertEquals('users.id', $relation->getQualifiedParentKeyName());
        $this->assertEquals('subscriptions.subscriber_id', $relation->getQualifiedForeignKeyName());
    }

    public function test_threads(): void
    {
        $model = new User;
        $relation = $model->threads();

        $this->assertEquals('thread_user', $relation->getTable());
        $this->assertEquals('users.id', $relation->getQualifiedParentKeyName());
        $this->assertEquals('thread_user.user_id', $relation->getQualifiedForeignPivotKeyName());
        $this->assertEquals('thread_user.thread_id', $relation->getQualifiedRelatedPivotKeyName());
    }

    public function test_get_profile_image_url_attribute(): void
    {
        $user = new User([
            'profileImage' => new ProfileImage([
                'url' => 'http://test.test/test.jpg',
            ]),
        ]);

        $this->assertEquals('http://test.test/test.jpg', $user->profile_image_url);
    }

    public function test_get_jwt_identifier(): void
    {
        $user = new User;
        $user->id = 4352;

        $this->assertEquals(4352, $user->getJWTIdentifier());
    }

    public function test_get_jwt_custom_claims(): void
    {
        $user = new User;

        $this->assertEquals([], $user->getJWTCustomClaims());
    }

    public function test_current_subscription(): void
    {
        $noSubscriptionsUser = new User([
            'subscriptions' => new Collection([
            ]),
        ]);

        $this->assertNull($noSubscriptionsUser->currentSubscription());

        $subscription = new Subscription([
            'expires_at' => null,
            'membershipPlanRate' => new MembershipPlanRate([
                'membershipPlan' => new MembershipPlan([
                    'duration' => MembershipPlan::DURATION_LIFETIME,
                ]),
            ]),
        ]);
        $lifetimeSubscriptionUser = new User([
            'subscriptions' => new Collection([
                $subscription,
            ]),
        ]);

        $this->assertEquals($subscription, $lifetimeSubscriptionUser->currentSubscription());

        $subscription = new Subscription([
            'expires_at' => (new Carbon)->addMonth(),
            'membershipPlanRate' => new MembershipPlanRate([
                'membershipPlan' => new MembershipPlan([
                    'duration' => MembershipPlan::DURATION_YEAR,
                ]),
            ]),
        ]);
        $activeSubscriptionUser = new User([
            'subscriptions' => new Collection([
                $subscription,
            ]),
        ]);

        $this->assertEquals($subscription, $activeSubscriptionUser->currentSubscription());

        $expiredSubscriptionUser = new User([
            'subscriptions' => new Collection([
                new Subscription([
                    'expires_at' => (new Carbon)->subMonth(),
                    'membershipPlanRate' => new MembershipPlanRate([
                        'membershipPlan' => new MembershipPlan([
                            'duration' => MembershipPlan::DURATION_YEAR,
                        ]),
                    ]),
                ]),
            ]),
        ]);

        $this->assertNull($expiredSubscriptionUser->currentSubscription());

        $withoutExpirationUser = new User([
            'subscriptions' => new Collection([
                new Subscription([
                    'membershipPlanRate' => new MembershipPlanRate([
                        'membershipPlan' => new MembershipPlan([
                            'duration' => MembershipPlan::DURATION_YEAR,
                        ]),
                    ]),
                ]),
            ]),
        ]);

        $this->assertNull($withoutExpirationUser->currentSubscription());
    }

    public function test_get_is_super_admin_attribute_true(): void
    {
        /** @var User $user */
        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('hasRole')->once()->with(Role::SUPER_ADMIN)->andReturn(true);

        $this->assertTrue($user->is_super_admin);
    }

    public function test_get_is_super_admin_attribute_false(): void
    {
        /** @var User $user */
        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('hasRole')->once()->with(Role::SUPER_ADMIN)->andReturn(false);

        $this->assertFalse($user->is_super_admin);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
