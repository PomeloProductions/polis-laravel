<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Listeners\User\Contact;

use App\Listeners\User\Contact\ContactCreatedListener;
use App\Models\User\Contact;
use App\Models\User\User;
use Polis\Contracts\Repositories\Messaging\MessageRepositoryContract;
use Polis\Events\User\Contact\ContactCreatedEvent;
use Polis\Tests\TestCase;

/**
 * Class ContactCreatedListenerTest
 */
final class ContactCreatedListenerTest extends TestCase
{
    public function test_handle(): void
    {
        $messageRepository = mock(MessageRepositoryContract::class);
        $listener = new ContactCreatedListener($messageRepository);

        $contact = new Contact([
            'initiatedBy' => new User([
                'first_name' => 'Steve',
                'last_name' => 'Brown',
            ]),
        ]);
        $event = new ContactCreatedEvent($contact);

        $messageRepository->shouldReceive('create')->once();

        $listener->handle($event);
    }
}
