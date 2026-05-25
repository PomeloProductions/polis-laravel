<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Models\User;

use App\Models\User\Contact;
use Polis\Tests\TestCase;

/**
 * Class ContactTest
 */
final class ContactTest extends TestCase
{
    public function test_initiated_by(): void
    {
        $contact = new Contact;
        $relation = $contact->initiatedBy();

        $this->assertEquals('contacts.initiated_by_id', $relation->getQualifiedForeignKeyName());
        $this->assertEquals('users.id', $relation->getQualifiedOwnerKeyName());
    }

    public function test_requested(): void
    {
        $contact = new Contact;
        $relation = $contact->requested();

        $this->assertEquals('contacts.requested_id', $relation->getQualifiedForeignKeyName());
        $this->assertEquals('users.id', $relation->getQualifiedOwnerKeyName());
    }
}
