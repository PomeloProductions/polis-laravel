<?php

declare(strict_types=1);

namespace Polis\Listeners\User\Contact;

use App\Models\Messaging\Message;
use Polis\Contracts\Repositories\Messaging\MessageRepositoryContract;
use Polis\Contracts\Services\Messaging\PushTemplateRenderingServiceContract;
use Polis\Events\User\Contact\ContactCreatedEvent;

/**
 * Class ContactCreatedListener
 *
 * Sends a push notification when one user requests to connect with
 * another. Migrated from PolisOS in v0.2 and refactored here to consume
 * the runtime-editable push template system instead of constructing the
 * notification body inline.
 *
 * Lookup hierarchy for the rendered push notification (delegated to
 * PushTemplateRenderingService): org-scoped PushTemplate row -> global
 * PushTemplate row -> DefaultPushTemplates::TEMPLATES['contact_created']
 * -> throw. Both the title and the body are interpolated with
 * `{{ contact.initiator.first_name }}`-style placeholders so the copy
 * can be edited per-tenant without a code change.
 *
 * The downstream `data` payload (sound, icon, click_action) is still
 * hardcoded here because those are platform delivery hints rather than
 * user-facing copy.
 */
class ContactCreatedListener
{
    public function __construct(
        private readonly MessageRepositoryContract $messageRepository,
        private readonly PushTemplateRenderingServiceContract $pushRendering,
    ) {}

    public function handle(ContactCreatedEvent $event): void
    {
        $contact = $event->getContact();
        $initiator = $contact->initiatedBy;

        // Organization scoping for per-tenant template overrides. The
        // Contact model doesn't currently carry an explicit
        // organization_id; if a future revision adds one, prefer that
        // here. For now, null = use the global template (or in-code
        // default).
        $organizationId = null;

        $rendered = $this->pushRendering->render(
            'contact_created',
            [
                'contact' => [
                    'initiator' => [
                        'first_name' => $initiator->first_name ?? '',
                        'last_name' => $initiator->last_name ?? '',
                    ],
                ],
            ],
            $organizationId,
        );

        $this->messageRepository->create([
            'subject' => $rendered->title,
            'to_id' => $contact->requested_id,
            'data' => [
                'body' => $rendered->body,
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
