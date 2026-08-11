<?php
declare(strict_types=1);

namespace App\Listeners\Organization;

use Polis\Contracts\Repositories\Messaging\MessageRepositoryContract;
use Polis\Events\Organization\OrganizationManagerCreatedEvent;

/**
 * Class OrganizationManagerCreatedListener
 * @package App\Listeners\Organization\
 */
class OrganizationManagerCreatedListener
{
    /**
     * @var MessageRepositoryContract
     */
    private MessageRepositoryContract $messageRepository;

    /**
     * OrganizationManagerCreatedListener constructor.
     * @param MessageRepositoryContract $messageRepository
     */
    public function __construct(MessageRepositoryContract $messageRepository)
    {
        $this->messageRepository = $messageRepository;
    }

    public function handle(OrganizationManagerCreatedEvent $event)
    {
        $organizationManager = $event->getOrganizationManager();
        $tempPassword = $event->getTempPassword();
        $subject = 'You have been granted access to the organization ' . $organizationManager->organization->name . '.';
        $this->messageRepository->sendEmailToUser($organizationManager->user, $subject, 'organization-manager-created', [
            'organization_name' => $organizationManager->organization->name,
            'organization_role' => $organizationManager->role->name,
            'temp_password' => $tempPassword,
        ]);
    }
}