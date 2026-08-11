<?php
declare(strict_types=1);

namespace App\Listeners\User;

use Polis\Contracts\Repositories\Messaging\MessageRepositoryContract;
use Polis\Events\User\SignUpEvent;

/**
 * Class SignUpListener
 * @package App\Listeners\User
 */
class SignUpListener
{
    /**
     * @var MessageRepositoryContract
     */
    private $messageRepository;

    /**
     * SignUpListener constructor.
     * @param MessageRepositoryContract $messageRepository
     */
    public function __construct(MessageRepositoryContract $messageRepository)
    {
        $this->messageRepository = $messageRepository;
    }

    /**
     * Creates a sign up email for when a user signs up.
     *
     * @param SignUpEvent $event
     */
    public function handle(SignUpEvent $event)
    {
        $user = $event->getUser();

        $this->messageRepository->sendEmailToUser(
            $user,
            'Welcome to Project Athenia!',
            'sign-up',
            [],
            $user->first_name . ',',
        );
    }
}