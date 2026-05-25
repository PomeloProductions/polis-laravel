<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Events\User\Contact;

use App\Models\User\Contact;
use Polis\Events\User\Contact\ContactCreatedEvent;
use Polis\Tests\TestCase;

/**
 * Class ContactCreatedEventTest
 */
final class ContactCreatedEventTest extends TestCase
{
    public function test_get_contact(): void
    {
        $contact = new Contact;

        $event = new ContactCreatedEvent($contact);

        $this->assertEquals($contact, $event->getContact());
    }
}
