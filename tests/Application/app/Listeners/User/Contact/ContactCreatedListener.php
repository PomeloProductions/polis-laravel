<?php
declare(strict_types=1);

namespace App\Listeners\User\Contact;

use Polis\Contracts\Repositories\Messaging\MessageRepositoryContract;
use Polis\Events\User\Contact\ContactCreatedEvent;
use App\Models\Messaging\Message;

/**
 * Class ContactCreatedListener
 * @package App\Listeners\User\Contact
 */
class ContactCreatedListener
{
    /**
     * @var MessageRepositoryContract
     */
    private $messageRepository;

    /**
     * ContactCreatedListener constructor.
     * @param MessageRepositoryContract $messageRepository
     */
    public function __construct(MessageRepositoryContract $messageRepository)
    {
        $this->messageRepository = $messageRepository;
    }

    /**
     * Sends the notification properly
     *
     * @param ContactCreatedEvent $event
     */
    public function handle(ContactCreatedEvent $event)
    {
        $contact = $event->getContact();

        $this->messageRepository->create([
            'subject' => 'New Contact Request!',
            'to_id' => $contact->requested_id,
            'data' => [
                'body' => $contact->initiatedBy->first_name . ' ' . $contact->initiatedBy->last_name
                        . ' wants to connect with you!',
                'sound' => '',
                'icon' => '',
                'click_action' => '',
            ],
            'via' => [
                Message::VIA_PUSH_NOTIFICATION,
            ],
            'action' => '/user/' . $contact->initiated_by_id,
        ]);
    }
}
