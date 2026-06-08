<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Policies;

use Mockery;
use Polis\Contracts\Models\HasPolicyContract;
use Polis\Contracts\Models\IsAnEntityContract;
use Polis\Tests\Fixtures\Models\ArticleNote;
use Polis\Tests\Fixtures\Models\Contact;
use Polis\Tests\Fixtures\Models\InvitationToken;
use Polis\Tests\Fixtures\Models\UserPage;
use Polis\Tests\Fixtures\Models\UserPageComponent;
use Polis\Tests\Fixtures\Policies\User\ArticleNotePolicy;
use Polis\Tests\Fixtures\Policies\User\ContactPolicy;
use Polis\Tests\Fixtures\Policies\User\InvitationTokenPolicy;
use Polis\Tests\Fixtures\Policies\User\ProfileImagePolicy;
use Polis\Tests\Fixtures\Policies\User\UserPageComponentPolicy;
use Polis\Tests\Fixtures\Policies\User\UserPagePolicy;
use Polis\Tests\Fixtures\Policies\User\UserPolicy;
use Polis\Tests\TestCase;

/**
 * Coverage for the User-namespaced policy abstracts. Most gate methods
 * here resolve to "logged-in user must match the requested user" plus
 * a row-ownership cross-check (user_id, initiated_by_id / requested_id,
 * user_page_id, etc.) on the target model.
 */
final class UserPolicyAbstractTest extends TestCase
{
    public function test_user_policy_all_returns_false(): void
    {
        $policy = new UserPolicy;
        $this->assertFalse($policy->all(Mockery::mock('App\\Models\\User\\User')));
    }

    public function test_user_policy_view_self_returns_true(): void
    {
        $policy = new UserPolicy;
        $this->assertTrue($policy->viewSelf(Mockery::mock('App\\Models\\User\\User')));
    }

    public function test_user_policy_view_returns_true(): void
    {
        $policy = new UserPolicy;
        $model = Mockery::mock(HasPolicyContract::class);
        $this->assertTrue($policy->view(Mockery::mock('App\\Models\\User\\User'), $model));
    }

    public function test_user_policy_create_returns_false(): void
    {
        $policy = new UserPolicy;
        $this->assertFalse($policy->create(Mockery::mock('App\\Models\\User\\User')));
    }

    public function test_user_policy_update_allows_self(): void
    {
        $policy = new UserPolicy;
        $user = Mockery::mock('App\\Models\\User\\User');
        $user->id = 5;
        $model = Mockery::mock(HasPolicyContract::class);
        $model->id = 5;

        $this->assertTrue($policy->update($user, $model));
    }

    public function test_user_policy_update_denies_other(): void
    {
        $policy = new UserPolicy;
        $user = Mockery::mock('App\\Models\\User\\User');
        $user->id = 5;
        $model = Mockery::mock(HasPolicyContract::class);
        $model->id = 9;

        $this->assertFalse($policy->update($user, $model));
    }

    public function test_user_policy_delete_returns_false(): void
    {
        $policy = new UserPolicy;
        $user = Mockery::mock('App\\Models\\User\\User');
        $model = Mockery::mock('App\\Models\\User\\User');

        $this->assertFalse($policy->delete($user, $model));
    }

    public function test_article_note_policy_all_allows_self(): void
    {
        $policy = new ArticleNotePolicy;
        $loggedIn = Mockery::mock('App\\Models\\User\\User');
        $loggedIn->id = 3;
        $requested = Mockery::mock('App\\Models\\User\\User');
        $requested->id = 3;

        $this->assertTrue($policy->all($loggedIn, $requested));
    }

    public function test_article_note_policy_create_denies_other(): void
    {
        $policy = new ArticleNotePolicy;
        $loggedIn = Mockery::mock('App\\Models\\User\\User');
        $loggedIn->id = 3;
        $requested = Mockery::mock('App\\Models\\User\\User');
        $requested->id = 9;

        $this->assertFalse($policy->create($loggedIn, $requested));
    }

    public function test_article_note_policy_view_allows_when_all_ids_match(): void
    {
        $policy = new ArticleNotePolicy;
        $user = Mockery::mock('App\\Models\\User\\User');
        $user->id = 3;
        $note = new ArticleNote;
        $note->user_id = 3;

        $this->assertTrue($policy->view($user, $user, $note));
    }

    public function test_article_note_policy_view_denies_when_note_owner_differs(): void
    {
        $policy = new ArticleNotePolicy;
        $user = Mockery::mock('App\\Models\\User\\User');
        $user->id = 3;
        $note = new ArticleNote;
        $note->user_id = 999;

        $this->assertFalse($policy->view($user, $user, $note));
    }

    public function test_article_note_policy_update_and_delete(): void
    {
        $policy = new ArticleNotePolicy;
        $user = Mockery::mock('App\\Models\\User\\User');
        $user->id = 3;
        $note = new ArticleNote;
        $note->user_id = 3;

        $this->assertTrue($policy->update($user, $user, $note));
        $this->assertTrue($policy->delete($user, $user, $note));
    }

    public function test_contact_policy_all_allows_self(): void
    {
        $policy = new ContactPolicy;
        $user = Mockery::mock('App\\Models\\User\\User');
        $user->id = 1;

        $this->assertTrue($policy->all($user, $user));
    }

    public function test_contact_policy_create_denies_other_user(): void
    {
        $policy = new ContactPolicy;
        $loggedIn = Mockery::mock('App\\Models\\User\\User');
        $loggedIn->id = 1;
        $requested = Mockery::mock('App\\Models\\User\\User');
        $requested->id = 2;

        $this->assertFalse($policy->create($loggedIn, $requested));
    }

    public function test_contact_policy_update_allows_initiator(): void
    {
        $policy = new ContactPolicy;
        $user = Mockery::mock('App\\Models\\User\\User');
        $user->id = 1;
        $contact = new Contact;
        $contact->initiated_by_id = 1;
        $contact->requested_id = 2;

        $this->assertTrue($policy->update($user, $user, $contact));
    }

    public function test_contact_policy_update_allows_requested(): void
    {
        $policy = new ContactPolicy;
        $user = Mockery::mock('App\\Models\\User\\User');
        $user->id = 2;
        $contact = new Contact;
        $contact->initiated_by_id = 1;
        $contact->requested_id = 2;

        $this->assertTrue($policy->update($user, $user, $contact));
    }

    public function test_contact_policy_update_denies_unrelated_user(): void
    {
        $policy = new ContactPolicy;
        $user = Mockery::mock('App\\Models\\User\\User');
        $user->id = 99;
        $contact = new Contact;
        $contact->initiated_by_id = 1;
        $contact->requested_id = 2;

        $this->assertFalse($policy->update($user, $user, $contact));
    }

    public function test_contact_policy_delete_allows_either_party(): void
    {
        $policy = new ContactPolicy;
        $user = Mockery::mock('App\\Models\\User\\User');
        $user->id = 1;
        $contact = new Contact;
        $contact->initiated_by_id = 1;
        $contact->requested_id = 2;

        $this->assertTrue($policy->delete($user, $user, $contact));
    }

    public function test_invitation_token_policy_all_returns_false(): void
    {
        $policy = new InvitationTokenPolicy;
        $this->assertFalse($policy->all(Mockery::mock('App\\Models\\User\\User')));
    }

    public function test_invitation_token_policy_view_returns_false(): void
    {
        $policy = new InvitationTokenPolicy;
        $token = new InvitationToken;
        $this->assertFalse($policy->view(Mockery::mock('App\\Models\\User\\User'), $token));
    }

    public function test_invitation_token_policy_create_returns_false(): void
    {
        $policy = new InvitationTokenPolicy;
        $this->assertFalse($policy->create(Mockery::mock('App\\Models\\User\\User')));
    }

    public function test_invitation_token_policy_update_returns_false(): void
    {
        $policy = new InvitationTokenPolicy;
        $token = new InvitationToken;
        $this->assertFalse($policy->update(Mockery::mock('App\\Models\\User\\User'), $token));
    }

    public function test_invitation_token_policy_delete_returns_false(): void
    {
        $policy = new InvitationTokenPolicy;
        $token = new InvitationToken;
        $this->assertFalse($policy->delete(Mockery::mock('App\\Models\\User\\User'), $token));
    }

    public function test_profile_image_policy_create_requires_entity_manage(): void
    {
        $policy = new ProfileImagePolicy;
        $user = Mockery::mock('App\\Models\\User\\User');
        $entity = Mockery::mock(IsAnEntityContract::class);
        $entity->shouldReceive('canUserManageEntity')->once()->with($user)->andReturn(true);

        $this->assertTrue($policy->create($user, $entity));
    }

    public function test_user_page_policy_all_allows_self(): void
    {
        $policy = new UserPagePolicy;
        $user = Mockery::mock('App\\Models\\User\\User');
        $user->id = 1;

        $this->assertTrue($policy->all($user, $user));
    }

    public function test_user_page_policy_view_allows_when_page_owned_by_self(): void
    {
        $policy = new UserPagePolicy;
        $user = Mockery::mock('App\\Models\\User\\User');
        $user->id = 1;
        $page = new UserPage;
        $page->user_id = 1;

        $this->assertTrue($policy->view($user, $user, $page));
    }

    public function test_user_page_policy_view_denies_when_page_owned_by_other(): void
    {
        $policy = new UserPagePolicy;
        $user = Mockery::mock('App\\Models\\User\\User');
        $user->id = 1;
        $page = new UserPage;
        $page->user_id = 2;

        $this->assertFalse($policy->view($user, $user, $page));
    }

    public function test_user_page_policy_create_allows_self(): void
    {
        $policy = new UserPagePolicy;
        $user = Mockery::mock('App\\Models\\User\\User');
        $user->id = 1;

        $this->assertTrue($policy->create($user, $user));
    }

    public function test_user_page_policy_update_allows_when_owned(): void
    {
        $policy = new UserPagePolicy;
        $user = Mockery::mock('App\\Models\\User\\User');
        $user->id = 1;
        $page = new UserPage;
        $page->user_id = 1;

        $this->assertTrue($policy->update($user, $user, $page));
    }

    public function test_user_page_policy_delete_denies_when_required(): void
    {
        $policy = new UserPagePolicy;
        $user = Mockery::mock('App\\Models\\User\\User');
        $user->id = 1;
        $page = new UserPage;
        $page->user_id = 1;
        $page->is_required = true;

        $this->assertFalse($policy->delete($user, $user, $page));
    }

    public function test_user_page_policy_delete_allows_optional_page(): void
    {
        $policy = new UserPagePolicy;
        $user = Mockery::mock('App\\Models\\User\\User');
        $user->id = 1;
        $page = new UserPage;
        $page->user_id = 1;
        $page->is_required = false;

        $this->assertTrue($policy->delete($user, $user, $page));
    }

    public function test_user_page_component_policy_all_allows_self_and_owned_page(): void
    {
        $policy = new UserPageComponentPolicy;
        $user = Mockery::mock('App\\Models\\User\\User');
        $user->id = 1;
        $page = new UserPage;
        $page->user_id = 1;

        $this->assertTrue($policy->all($user, $user, $page));
    }

    public function test_user_page_component_policy_create_allows_self_and_owned_page(): void
    {
        $policy = new UserPageComponentPolicy;
        $user = Mockery::mock('App\\Models\\User\\User');
        $user->id = 1;
        $page = new UserPage;
        $page->user_id = 1;

        $this->assertTrue($policy->create($user, $user, $page));
    }

    public function test_user_page_component_policy_update_validates_page_link(): void
    {
        $policy = new UserPageComponentPolicy;
        $user = Mockery::mock('App\\Models\\User\\User');
        $user->id = 1;
        $page = new UserPage;
        $page->id = 7;
        $page->user_id = 1;
        $component = new UserPageComponent;
        $component->user_page_id = 7;

        $this->assertTrue($policy->update($user, $user, $page, $component));
    }

    public function test_user_page_component_policy_update_denies_when_component_belongs_to_different_page(): void
    {
        $policy = new UserPageComponentPolicy;
        $user = Mockery::mock('App\\Models\\User\\User');
        $user->id = 1;
        $page = new UserPage;
        $page->id = 7;
        $page->user_id = 1;
        $component = new UserPageComponent;
        $component->user_page_id = 99;

        $this->assertFalse($policy->update($user, $user, $page, $component));
    }

    public function test_user_page_component_policy_delete_validates_page_link(): void
    {
        $policy = new UserPageComponentPolicy;
        $user = Mockery::mock('App\\Models\\User\\User');
        $user->id = 1;
        $page = new UserPage;
        $page->id = 7;
        $page->user_id = 1;
        $component = new UserPageComponent;
        $component->user_page_id = 7;

        $this->assertTrue($policy->delete($user, $user, $page, $component));
    }

    public function test_user_page_component_policy_all_denies_when_page_belongs_to_other(): void
    {
        $policy = new UserPageComponentPolicy;
        $user = Mockery::mock('App\\Models\\User\\User');
        $user->id = 1;
        $page = new UserPage;
        $page->user_id = 99;

        $this->assertFalse($policy->all($user, $user, $page));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
