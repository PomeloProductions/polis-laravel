<?php

declare(strict_types=1);

namespace Polis\Listeners\User;

use Polis\Contracts\Repositories\Messaging\MessageRepositoryContract;
use Polis\Events\User\ForgotPasswordEvent;

/**
 * Class ForgotPasswordListener
 */
class ForgotPasswordListener
{
    /**
     * @var MessageRepositoryContract
     */
    private $messageRepository;

    /**
     * ForgotPasswordListener constructor.
     */
    public function __construct(MessageRepositoryContract $messageRepository)
    {
        $this->messageRepository = $messageRepository;
    }

    /**
     * Sends the forgot password email to a user
     */
    public function handle(ForgotPasswordEvent $event)
    {
        $passwordToken = $event->getPasswordToken();

        $this->messageRepository->create([
            'subject' => 'Reset Password Request',
            'template' => 'forgot-password',
            'email' => $passwordToken->user->email,
            'data' => [
                'greeting' => 'Hello '.$passwordToken->user->first_name.',',
                'token' => $passwordToken->token,
            ],
        ], $passwordToken->user);
    }
}
