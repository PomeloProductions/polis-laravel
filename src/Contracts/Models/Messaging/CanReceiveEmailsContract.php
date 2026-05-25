<?php

declare(strict_types=1);

namespace Polis\Contracts\Models\Messaging;

interface CanReceiveEmailsContract extends CanReceiveMessageContract
{
    /**
     * The email address to send the email to
     */
    public function getEmailAddress(): string;

    /**
     * The name of the person to be added as the to field
     */
    public function getEmailToName(): string;
}
