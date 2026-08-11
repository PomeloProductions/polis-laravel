<?php

declare(strict_types=1);

namespace Polis\Tests\Integration\Policies\User;

use App\Models\User\Contact;
use App\Models\User\User;
use App\Policies\User\ContactPolicy;
use Polis\Tests\Application\ApplicationTestCase;

/**
 * Class ContactPolicyTest
 */
final class ContactPolicyTest extends ApplicationTestCase
{
    
    public function test_all_passes(): void
    {
        $user = User::factory()->create();

        $policy = new ContactPolicy;

        $this->assertTrue($policy->all($user, $user));
    }

    public function test_all_fails(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $policy = new ContactPolicy;

        $this->assertFalse($policy->all($user1, $user2));
    }

    public function test_create_passes(): void
    {
        $user = User::factory()->create();

        $policy = new ContactPolicy;

        $this->assertTrue($policy->create($user, $user));
    }

    public function test_create_fails(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $policy = new ContactPolicy;

        $this->assertFalse($policy->create($user1, $user2));
    }

    public function test_update_passes(): void
    {
        $user = User::factory()->create();

        $policy = new ContactPolicy;

        $initiatedContact = Contact::factory()->create([
            'initiated_by_id' => $user->id,
        ]);
        $this->assertTrue($policy->update($user, $user, $initiatedContact));

        $requestedContact = Contact::factory()->create([
            'requested_id' => $user->id,
        ]);
        $this->assertTrue($policy->update($user, $user, $requestedContact));
    }

    public function test_update_fails(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $contact = Contact::factory()->create([
            'initiated_by_id' => $user2->id,
        ]);

        $policy = new ContactPolicy;

        $this->assertFalse($policy->update($user1, $user2, $contact));
        $this->assertFalse($policy->update($user1, $user1, $contact));
    }

    public function test_delete_passes(): void
    {
        $user = User::factory()->create();

        $policy = new ContactPolicy;

        $initiatedContact = Contact::factory()->create([
            'initiated_by_id' => $user->id,
        ]);
        $this->assertTrue($policy->update($user, $user, $initiatedContact));

        $requestedContact = Contact::factory()->create([
            'requested_id' => $user->id,
        ]);
        $this->assertTrue($policy->delete($user, $user, $requestedContact));
    }

    public function test_delete_fails(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $contact = Contact::factory()->create([
            'initiated_by_id' => $user2->id,
        ]);

        $policy = new ContactPolicy;

        $this->assertFalse($policy->delete($user1, $user2, $contact));
        $this->assertFalse($policy->delete($user1, $user1, $contact));
    }
}
