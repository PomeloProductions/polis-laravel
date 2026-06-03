<?php

declare(strict_types=1);

namespace Polis\Listeners\User\Contact;

use App\Models\Messaging\Message;
use Polis\Contracts\Repositories\Messaging\MessageRepositoryContract;
use Polis\Events\User\Contact\ContactCreatedEvent;

/**
 * Class ContactCreatedListener
 *
 * Sends a push notification when one user requests to connect with another.
 * Migrated from PolisOS's app/Listeners/User/Contact/ContactCreatedListener.php
 * — the body text and notification metadata are intentionally still
 * hardcoded here.
 *
 * Why not templated? v0.2's EmailTemplate system covers email only. Push
 * notification templating (with localized strings, per-tenant overrides,
 * etc.) is a follow-up — see the v0.2 PR body's "Follow-up" section. When
 * that lands, this listener should switch over to a `PushTemplate`-backed
 * approach analogous to TemplatedMailable.
 *
 * @todo Convert hardcoded message body to a PushNotificationTemplate system
 *   once that's introduced (planned follow-up after v0.2).
 */
class ContactCreatedListener
{
    public function __construct(
        private readonly MessageRepositoryContract $messageRepository,
    ) {}

    public function handle(ContactCreatedEvent $event): void
    {
        $contact = $event->getContact();
        $initiator = $contact->initiatedBy;
        $body = trim(($initiator->first_name ?? '').' '.($initiator->last_name ?? '')).' wants to connect with you!';

        $this->messageRepository->create([
            'subject' => 'New Contact Request!',
            'to_id' => $contact->requested_id,
            'data' => [
                'body' => $body,
                'sound' => '',
                'icon' => '',
                'click_action' => '',
            ],
            'via' => [
                Message::VIA_PUSH_NOTIFICATION,
            ],
            'action' => '/user/'.$contact->initiated_by_id,
        ]);
    }
}
