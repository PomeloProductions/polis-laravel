<?php

declare(strict_types=1);

namespace Polis\Listeners\User;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Mail\Mailer;
use Polis\Events\User\SignUpEvent;
use Polis\Mail\TemplatedMailable;

/**
 * Class SignUpListener
 *
 * Sends a welcome email to a newly-signed-up user. Migrated from PolisOS's
 * app/Listeners/User/SignUpListener.php — the original used the old
 * MessageRepository->sendEmailToUser pipeline with a hardcoded blade template
 * name; v0.2 uses the runtime-editable EmailTemplate system instead.
 *
 * Template key: `welcome` (see Polis\Mail\DefaultEmailTemplates).
 */
class SignUpListener
{
    public function __construct(
        private readonly Mailer $mailer,
        private readonly Repository $config,
    ) {}

    public function handle(SignUpEvent $event): void
    {
        $user = $event->getUser();

        $variables = [
            'user' => [
                'first_name' => $user->first_name ?? '',
                'last_name' => $user->last_name ?? '',
                'email' => $user->email ?? '',
            ],
            'app' => [
                'name' => $this->config->get('app.name', 'Polis'),
            ],
        ];

        $this->mailer->to($user->email)->send(new TemplatedMailable(
            templateKey: 'welcome',
            variables: $variables,
        ));
    }
}
